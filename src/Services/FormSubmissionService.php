<?php
namespace CMS\Services;

use CMS\Core\Database;
use CMS\Core\Vault;

class FormSubmissionService {

    // Automatyczna migracja struktury bazy (doda kolumnę w tle jeśli nie istnieje)
    private function ensureStatusColumnExists($db) {
        try {
            $db->query("ALTER TABLE pa_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted' AFTER user_ip");
        } catch (\Exception $e) { /* Kolumna już istnieje */ }
    }

    public function handleSubmission($formId, $submissionId, $formData, $files, $userId) {
        $vault = new Vault();
        $db = Database::getInstance();
        $this->ensureStatusColumnExists($db);

        // --- 1. WERYFIKACJA ANTY-SPAMOWA I ODCZYT USTAWIEŃ ---
        $formDef = $db->query("SELECT form_json, settings FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        $fields = json_decode($formDef['form_json'] ?? '[]', true);
        $formSettings = json_decode($formDef['settings'] ?? '{}', true);
        $allowMultiple = !empty($formSettings['allowMultipleFiles']);

        foreach ($fields as $f) {
            $type = $f['type'] ?? '';

            if ($type === 'honeypot') {
                $hpId = $f['custom_id'] ?? $f['id'] ?? null;
                foreach ($_POST as $k => $v) {
                    if (strpos($k, 'hp_data_') === 0 && !empty(trim($v))) return ['status' => 'error', 'msg' => 'Wykryto niedozwoloną aktywność automatyczną.'];
                }
            }

            if ($type === 'captcha_image') {
                $expected = \CMS\Core\Session::get('captcha_img_' . $formId);
                $provided = $_POST['captcha_answer'] ?? '';
                if (!$expected || strtolower(trim($provided)) !== strtolower($expected)) return ['status' => 'error', 'msg' => 'Błędny kod z obrazka. Spróbuj ponownie.'];
                \CMS\Core\Session::remove('captcha_img_' . $formId);
            }

            if ($type === 'captcha_turnstile') {
                $secretKey = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'turnstile_secret_key'")->fetch()['setting_value'] ?? '';
                $token = $_POST['cf-turnstile-response'] ?? '';

                if (empty($token)) return ['status' => 'error', 'msg' => 'Zaznacz pole zabezpieczające "Nie jestem robotem".'];
                if (empty($secretKey)) return ['status' => 'error', 'msg' => 'Błąd systemu: Brak klucza Secret Key dla Turnstile.'];

                $verify = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, stream_context_create([
                    'http' => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query(['secret' => $secretKey, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR']])
                    ]
                ]));

                $captchaResponse = json_decode($verify);
                if (!$captchaResponse || !$captchaResponse->success) return ['status' => 'error', 'msg' => 'Weryfikacja Cloudflare Turnstile nie powiodła się. Spróbuj odświeżyć stronę.'];
            }
        }
        // -----------------------------------

        $encryptedData = $vault->encrypt(json_encode($formData));
        $savedFiles = [];

        if ($submissionId) {
            $oldSub = $db->query("SELECT files_json FROM pa_submissions WHERE id = :id", ['id' => $submissionId])->fetch();
            if ($oldSub && $oldSub['files_json']) $savedFiles = json_decode($oldSub['files_json'], true) ?? [];
        }

        // --- 2. ZARZĄDZANIE ASYNCHRONICZNYMI PLIKAMI ---
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'file') {
                $fieldKey = $f['custom_id'] ?? $f['id'] ?? null;
                if ($fieldKey) {
                    if (isset($_POST['async_files'][$fieldKey]) && is_array($_POST['async_files'][$fieldKey])) {
                        $parsedFiles = [];
                        foreach ($_POST['async_files'][$fieldKey] as $jsonStr) {
                            $cleanJson = html_entity_decode($jsonStr, ENT_QUOTES, 'UTF-8');
                            $fData = json_decode($cleanJson, true);
                            if (!$fData) $fData = json_decode($jsonStr, true);
                            if (is_array($fData) && !empty($fData['storage_name'])) $parsedFiles[$fData['storage_name']] = $fData;
                        }
                        $parsedFiles = array_values($parsedFiles);
                        if (!empty($parsedFiles)) {
                            $savedFiles[$fieldKey] = $allowMultiple ? $parsedFiles : end($parsedFiles);
                        } else unset($savedFiles[$fieldKey]);
                    } else unset($savedFiles[$fieldKey]);
                }
            }
        }

        $filesJson = json_encode($savedFiles);
        $userIp = $_SERVER['REMOTE_ADDR'];

        if ($submissionId) {
            // AKTUALIZACJA I ZMIANA STATUSU NA SUBMITTED
            $db->query("UPDATE pa_submissions SET data_json = :d, files_json = :f, user_ip = :ip, status = 'submitted' WHERE id = :id", [
                'd' => $encryptedData, 'f' => $filesJson, 'ip' => $userIp, 'id' => $submissionId
            ]);
            // Zakończenie sesji szkicu dla gościa
            if (!$userId) \CMS\Core\Session::remove('draft_' . $formId);
        } else {
            // NOWY WPIS (OD RAZU JAKO SUBMITTED)
            $db->query("INSERT INTO pa_submissions (form_id, user_id, user_ip, data_json, files_json, status) VALUES (:fid, :uid, :ip, :d, :f, 'submitted')", [
                'fid' => $formId, 'uid' => $userId, 'ip' => $userIp, 'd' => $encryptedData, 'f' => $filesJson
            ]);
        }

        $this->sendEmailNotifications($formId, $formData, $userId);
        return ['status' => 'success'];
    }

    // --- NOWA METODA AUTOSAVE DLA WERSJI ROBOCZYCH ---
    public function handleAutosave($formId, $submissionId, $formData, $userId) {
        $vault = new Vault();
        $db = Database::getInstance();
        $this->ensureStatusColumnExists($db);

        $encryptedData = $vault->encrypt(json_encode($formData));
        $userIp = $_SERVER['REMOTE_ADDR'];
        $filesJson = '[]';

        if ($submissionId) {
            $oldSub = $db->query("SELECT files_json, status FROM pa_submissions WHERE id = :id", ['id' => $submissionId])->fetch();
            if ($oldSub) {
                // Jeśli wpis został już całkowicie wysłany - BLOKUJEMY auto-save aby nie uszkodzić publicznych danych przyciskiem "edytuj"
                if ($oldSub['status'] === 'submitted') {
                    return ['status' => 'success', 'submission_id' => $submissionId];
                }
                $filesJson = $oldSub['files_json'];
            }
        }

        // Obsługa załączników w tle - żeby nie zniknęły przy zapisywaniu tekstu
        $formDef = $db->query("SELECT form_json, settings FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        $fields = json_decode($formDef['form_json'] ?? '[]', true);
        $formSettings = json_decode($formDef['settings'] ?? '{}', true);
        $allowMultiple = !empty($formSettings['allowMultipleFiles']);
        $savedFiles = json_decode($filesJson, true) ?? [];

        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'file') {
                $fieldKey = $f['custom_id'] ?? $f['id'] ?? null;
                if ($fieldKey) {
                    if (isset($_POST['async_files'][$fieldKey]) && is_array($_POST['async_files'][$fieldKey])) {
                        $parsedFiles = [];
                        foreach ($_POST['async_files'][$fieldKey] as $jsonStr) {
                            $cleanJson = html_entity_decode($jsonStr, ENT_QUOTES, 'UTF-8');
                            $fData = json_decode($cleanJson, true);
                            if (!$fData) $fData = json_decode($jsonStr, true);
                            if (is_array($fData) && !empty($fData['storage_name'])) $parsedFiles[$fData['storage_name']] = $fData;
                        }
                        $parsedFiles = array_values($parsedFiles);
                        if (!empty($parsedFiles)) $savedFiles[$fieldKey] = $allowMultiple ? $parsedFiles : end($parsedFiles);
                        else unset($savedFiles[$fieldKey]);
                    } else unset($savedFiles[$fieldKey]);
                }
            }
        }
        $filesJson = json_encode($savedFiles);

        if ($submissionId) {
            $db->query("UPDATE pa_submissions SET data_json = :d, files_json = :f, user_ip = :ip WHERE id = :id", [
                'd' => $encryptedData, 'f' => $filesJson, 'ip' => $userIp, 'id' => $submissionId
            ]);
        } else {
            $db->query("INSERT INTO pa_submissions (form_id, user_id, user_ip, data_json, files_json, status) VALUES (:fid, :uid, :ip, :d, :f, 'draft')", [
                'fid' => $formId, 'uid' => $userId, 'ip' => $userIp, 'd' => $encryptedData, 'f' => $filesJson
            ]);
            $submissionId = $db->getConnection()->lastInsertId();
            // Zapamiętanie tymczasowego szkicu dla gościa (niezalogowanego) w ciasteczku sesyjnym
            if (!$userId) {
                \CMS\Core\Session::set('draft_' . $formId, $submissionId);
            }
        }

        return ['status' => 'success', 'submission_id' => $submissionId];
    }

    private function sendEmailNotifications($formId, $formData, $userId) {
        // [TUTAJ POZOSTAJE TWÓJ DOTYCHCZASOWY KOD WYSYŁKI MAILI - ZAWIERAJĄCY WYWOŁANIA PHPMailer itp.]
        $db = Database::getInstance();
        $form = $db->query("SELECT settings FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form || empty($form['settings'])) return;

        $settings = json_decode($form['settings'], true);
        $emailSettings = $settings['email'] ?? null;
        if (!$emailSettings) return;

        $replacements = [];
        if (!empty($emailSettings['tags']) && is_array($emailSettings['tags'])) {
            foreach ($emailSettings['tags'] as $tagMap) {
                $tag = $tagMap['tag'] ?? '';
                $fieldId = $tagMap['field'] ?? '';
                if ($tag && $fieldId) {
                    $val = $formData[$fieldId] ?? '';
                    if (is_array($val)) $val = implode(', ', $val);
                    $replacements['{{' . $tag . '}}'] = htmlspecialchars((string)$val);
                }
            }
        }

        $mailer = new \CMS\Services\MailerService();

        if (!empty($emailSettings['sendAdmin']) && !empty($emailSettings['adminTemplate']) && !empty($emailSettings['adminList'])) {
            $template = $db->query("SELECT * FROM pa_email_templates WHERE id = ?", [$emailSettings['adminTemplate']])->fetch();
            if ($template) {
                $body = str_replace(array_keys($replacements), array_values($replacements), $template['body']);
                $subject = str_replace(array_keys($replacements), array_values($replacements), $template['subject']);
                $listId = $emailSettings['adminList'];
                
                $recipients = [];
                if ($listId === 'admins') {
                    $users = $db->query("SELECT email FROM pa_users WHERE admin = 1 AND email IS NOT NULL AND email != ''")->fetchAll();
                    foreach ($users as $u) $recipients[] = $u['email'];
                } else {
                    $subs = $db->query("SELECT email FROM pa_mailing_subscribers WHERE list_id = ?", [$listId])->fetchAll();
                    foreach ($subs as $s) $recipients[] = $s['email'];
                }

                if (!empty($recipients)) {
                    foreach (array_unique($recipients) as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $finalBody = str_replace('{{email}}', $email, $body);
                            $finalSubject = str_replace('{{email}}', $email, $subject);
                            $mailer->send($email, $finalSubject, $finalBody);
                        }
                    }
                }
            }
        }

        if (!empty($emailSettings['sendUser']) && !empty($emailSettings['userTemplate']) && !empty($emailSettings['userField'])) {
            $userEmailField = $emailSettings['userField'];
            $userEmail = null;

            if ($userEmailField === 'system_user_email') {
                if ($userId) {
                    $user = $db->query("SELECT email FROM pa_users WHERE id = ?", [$userId])->fetch();
                    $userEmail = $user['email'] ?? null;
                }
            } else {
                $userEmail = $formData[$userEmailField] ?? null;
            }

            if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $template = $db->query("SELECT * FROM pa_email_templates WHERE id = ?", [$emailSettings['userTemplate']])->fetch();
                if ($template) {
                    $body = str_replace(array_keys($replacements), array_values($replacements), $template['body']);
                    $subject = str_replace(array_keys($replacements), array_values($replacements), $template['subject']);
                    $finalBody = str_replace('{{email}}', $userEmail, $body);
                    $finalSubject = str_replace('{{email}}', $userEmail, $subject);
                    $mailer->send($userEmail, $finalSubject, $finalBody);
                }
            }
        }
    }
}