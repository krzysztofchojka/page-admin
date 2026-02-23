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

        // Jeśli to edycja, pobierzmy stare pliki, żeby ich nie wykasować z bazy
        if ($submissionId && $userId) {
            $oldSub = $db->query("SELECT files_json FROM pa_submissions WHERE id = :id AND user_id = :uid", [
                'id' => $submissionId, 
                'uid' => $userId
            ])->fetch();
            
            if ($oldSub && $oldSub['files_json']) {
                $savedFiles = json_decode($oldSub['files_json'], true) ?? [];
            }
        }

        // Przetwarzanie nowo wgranych plików (dla wszystkich pól typu file)
        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/secure/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($files['name'] as $fieldKey => $filename) {
                if ($files['error'][$fieldKey] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$fieldKey];
                    $content = file_get_contents($tmpName);
                    
                    // Szyfrowanie pliku
                    $encryptedContent = $vault->encrypt($content);
                    $safeName = bin2hex(random_bytes(16)) . '.enc';
                    file_put_contents($uploadDir . $safeName, $encryptedContent);

                    // Dodajemy/nadpisujemy plik dla konkretnego pola
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
            // EDYCJA
            $db->query("UPDATE pa_submissions SET data_json = :d, files_json = :f, user_ip = :ip WHERE id = :id AND user_id = :uid", [
                'd' => $encryptedData,
                'f' => $filesJson,
                'ip' => $userIp,
                'id' => $submissionId,
                'uid' => $userId
            ]);
        } else {
            // NOWE ZGŁOSZENIE
            $db->query("INSERT INTO pa_submissions (form_id, user_id, user_ip, data_json, files_json) VALUES (:fid, :uid, :ip, :d, :f)", [
                'fid' => $formId,
                'uid' => $userId,
                'ip' => $userIp,
                'd' => $encryptedData,
                'f' => $filesJson
            ]);
        }
        
        return true;
    }
}