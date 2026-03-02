<?php
namespace CMS\Services;

use CMS\Core\Database;
use CMS\Core\Vault;

class FormSubmissionService {
    public function handleSubmission($formId, $submissionId, $formData, $files, $userId) {
        $vault = new Vault();
        $db = Database::getInstance();
        $encryptedData = $vault->encrypt(json_encode($formData));
        $savedFiles = [];

        if ($submissionId && $userId) {
            $oldSub = $db->query("SELECT files_json FROM pa_submissions WHERE id = :id AND user_id = :uid", [
                'id' => $submissionId,
                'uid' => $userId
            ])->fetch();
            if ($oldSub && $oldSub['files_json']) {
                $savedFiles = json_decode($oldSub['files_json'], true) ?? [];
            }
        }

        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/secure/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($files['name'] as $fieldKey => $filename) {
                if ($files['error'][$fieldKey] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$fieldKey];
                    $content = file_get_contents($tmpName);
                    
                    $encryptedContent = $vault->encrypt($content);
                    $safeName = bin2hex(random_bytes(16)) . '.enc';
                    file_put_contents($uploadDir . $safeName, $encryptedContent);

                    $savedFiles[$fieldKey] = [
                        'original_name' => $filename,
                        'storage_name' => $safeName
                    ];
                }
            }
        }

        $filesJson = json_encode($savedFiles);
        $userIp = $_SERVER['REMOTE_ADDR'];

        if ($submissionId && $userId) {
            $db->query("UPDATE pa_submissions SET data_json = :d, files_json = :f, user_ip = :ip WHERE id = :id AND user_id = :uid", [
                'd' => $encryptedData,
                'f' => $filesJson,
                'ip' => $userIp,
                'id' => $submissionId,
                'uid' => $userId
            ]);
        } else {
            $db->query("INSERT INTO pa_submissions (form_id, user_id, user_ip, data_json, files_json) VALUES (:fid, :uid, :ip, :d, :f)", [
                'fid' => $formId,
                'uid' => $userId,
                'ip' => $userIp,
                'd' => $encryptedData,
                'f' => $filesJson
            ]);
            
            // WYSYŁKA POWIADOMIEŃ EMAIL
            $this->sendEmailNotifications($formId, $formData, $userId);
        }

        return true;
    }

    private function sendEmailNotifications($formId, $formData, $userId) {
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

        // 1. ADMIN
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

        // 2. KLIENT (Z UWZGLĘDNIENIEM KONTA SYSTEMOWEGO)
        if (!empty($emailSettings['sendUser']) && !empty($emailSettings['userTemplate']) && !empty($emailSettings['userField'])) {
            $userEmailField = $emailSettings['userField'];
            $userEmail = null;

            // Sprawdzamy czy to zaciąg systemowy
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