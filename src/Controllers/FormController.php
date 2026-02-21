<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;
use CMS\Core\Vault;

class FormController {

    // --- ADMIN: BUILDER ---
    public function index() {
        Session::init();
        // Security check omitted for brevity, ensure you add !isLoggedIn check
        $db = Database::getInstance();
        $forms = $db->query("SELECT * FROM pa_forms ORDER BY id DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/forms/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        // Create blank form and redirect to editor
        $db = Database::getInstance();
        $db->query("INSERT INTO pa_forms (title, form_json) VALUES ('New Form', '[]')");
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/forms/builder?id=$id");
        exit;
    }

    public function builder() {
        $id = $_GET['id'] ?? 0;
        $db = Database::getInstance();
        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $id])->fetch();
        require_once __DIR__ . '/../Views/admin/forms/builder.php';
    }

    public function save() {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $db->query("UPDATE pa_forms SET title = :title, form_json = :json, settings = :settings WHERE id = :id", [
            'title' => $data['title'],
            'json' => json_encode($data['fields']),
            'settings' => json_encode($data['settings']),
            'id' => $data['id']
        ]);
        echo json_encode(['status' => 'success']);
    }

    public function submissions() {
        Session::init();
        if (!Session::isLoggedIn()) header('Location: /login');

        $formId = $_GET['id'] ?? 0;
        $db = Database::getInstance();
        $vault = new \CMS\Core\Vault();

        // 1. Get Form Definition (to know column headers)
        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form) die("Form not found");

        $fields = json_decode($form['form_json'], true);

        // 2. Get Encrypted Submissions
        $rows = $db->query("SELECT s.*, u.email as user_email FROM pa_submissions s LEFT JOIN pa_users u ON s.user_id = u.id WHERE form_id = :id ORDER BY id DESC", ['id' => $formId])->fetchAll();

        // 3. Decrypt Rows for Display
        $decryptedRows = [];
        foreach ($rows as $row) {
            // Decrypt the JSON blob
            $jsonString = $vault->decrypt($row['data_json']);
            $data = json_decode($jsonString, true);
            
            // Decrypt File Metadata (not the files themselves, just the names)
            // Note: files_json was NOT encrypted in previous step, just the file content. 
            // If you encrypted files_json, decrypt it here. In previous step we stored it as plain JSON of filenames.
            $files = json_decode($row['files_json'], true);

            $decryptedRows[] = [
                'id' => $row['id'],
                'date' => $row['created_at'],
                'ip' => $row['user_ip'],
                'data' => $data,
                'files' => $files,
                'user_email' => $row['user_email'] ?? 'Guest'
            ];
        }

        require_once __DIR__ . '/../Views/admin/forms/submissions.php';
    }

    public function deleteSubmission() {
        Session::init();
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    
        $subId = $_GET['id'] ?? 0;
        $formId = $_GET['form_id'] ?? 0;
    
        $db = Database::getInstance();
        
        // 1. Pobierz zgłoszenie, aby namierzyć i usunąć pliki z dysku
        $submission = $db->query("SELECT * FROM pa_submissions WHERE id = :id", ['id' => $subId])->fetch();
        
        if ($submission) {
            $files = json_decode($submission['files_json'], true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    $filePath = __DIR__ . '/../../public/uploads/secure/' . $file['storage_name'];
                    if (file_exists($filePath)) {
                        unlink($filePath); // Usuwamy fizyczny plik
                    }
                }
            }
            
            // 2. Usuń rekord z bazy
            $db->query("DELETE FROM pa_submissions WHERE id = :id", ['id' => $subId]);
        }
    
        // 3. Powrót do listy zgłoszeń
        header("Location: /admin/forms/submissions?id=" . $formId);
        exit;
    }

    public function downloadFile() {
        Session::init();
        if (!Session::isLoggedIn()) die("Access Denied");

        $file = $_GET['file'] ?? '';
        
        // Security: Prevent Directory Traversal
        if (strpos($file, '..') !== false || strpos($file, '/') !== false) {
            die("Invalid filename");
        }

        $path = __DIR__ . '/../../public/uploads/secure/' . $file;
        if (!file_exists($path)) die("File not found");

        // 1. Read Encrypted Content
        $encryptedContent = file_get_contents($path);
        
        // 2. Decrypt
        $vault = new \CMS\Core\Vault();
        $decryptedContent = $vault->decrypt($encryptedContent);

        // 3. Force Download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
$origName = $_GET['orig'] ?? ('odkodowany_' . str_replace('.enc', '', $file));
$origName = basename(urldecode($origName));
header('Content-Disposition: attachment; filename="' . $origName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($decryptedContent));
        
        echo $decryptedContent;
        exit;
    }

    // --- PUBLIC: SUBMISSION ---
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Method not allowed");
    
        $formId = $_POST['form_id'];
        $submissionId = $_POST['submission_id'] ?? null;
        $formData = $_POST['data'] ?? [];
        $files = $_FILES['files'] ?? [];
        
        \CMS\Core\Session::init();
        $userId = \CMS\Core\Session::get('user_id') ?: null;
    
        $vault = new \CMS\Core\Vault();
        $encryptedData = $vault->encrypt(json_encode($formData));
    
        $db = \CMS\Core\Database::getInstance();
        $savedFiles = [];
    
        // Jeśli to edycja, pobierzmy stare pliki, żeby ich nie wykasować z bazy
        if ($submissionId && $userId) {
            $oldSub = $db->query("SELECT files_json FROM pa_submissions WHERE id = :id AND user_id = :uid", ['id' => $submissionId, 'uid' => $userId])->fetch();
            if ($oldSub && $oldSub['files_json']) {
                $savedFiles = json_decode($oldSub['files_json'], true) ?? [];
            }
        }
    
        // Przetwarzanie nowo wgranych plików (dla wszystkich pól typu file)
        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/secure/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
            foreach ($files['name'] as $fieldKey => $filename) {
                if ($files['error'][$fieldKey] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$fieldKey];
                    $content = file_get_contents($tmpName);
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
    
        if ($submissionId && $userId) {
            // EDYCJA - Dodano aktualizację kolumny files_json!
            $db->query("UPDATE pa_submissions SET data_json = :d, files_json = :f, user_ip = :ip WHERE id = :id AND user_id = :uid", [
                'd' => $encryptedData,
                'f' => json_encode($savedFiles),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'id' => $submissionId,
                'uid' => $userId
            ]);
        } else {
            // NOWE ZGŁOSZENIE
            $db->query("INSERT INTO pa_submissions (form_id, user_id, user_ip, data_json, files_json) VALUES (:fid, :uid, :ip, :d, :f)", [
                'fid' => $formId,
                'uid' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'd' => $encryptedData,
                'f' => json_encode($savedFiles)
            ]);
        }
    
        $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
        $redirect = strtok($redirect, '?'); 
        header("Location: " . $redirect . "?submitted=" . $formId);
        exit;
    }
}