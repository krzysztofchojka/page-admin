<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;
use CMS\Core\Vault;

class FormController {
    // --- ADMIN: BUILDER ---
    public function index() {
        Session::init();
        $db = Database::getInstance();
        $forms = $db->query("SELECT * FROM pa_forms ORDER BY id DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/forms/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
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

        // Pobieramy całe szablony, aby mieć dostęp do ich treści w JS (do tagów i podglądu)
        $emailTemplates = $db->query("SELECT id, title, subject, body FROM pa_email_templates ORDER BY title ASC")->fetchAll();
        $mailingLists = $db->query("SELECT id, name FROM pa_mailing_lists ORDER BY name ASC")->fetchAll();

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

        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form) die("Form not found");
        $fields = json_decode($form['form_json'], true);

        $rows = $db->query("SELECT s.*, u.email as user_email FROM pa_submissions s LEFT JOIN pa_users u ON s.user_id = u.id WHERE form_id = :id ORDER BY id DESC", ['id' => $formId])->fetchAll();

        $decryptedRows = [];
        foreach ($rows as $row) {
            $jsonString = $vault->decrypt($row['data_json']);
            $data = json_decode($jsonString, true);
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

        ob_start();
        require_once __DIR__ . '/../Views/admin/forms/submissions.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function deleteSubmission() {
        Session::init();
        if (!Session::isLoggedIn()) { header('Location: /login'); exit; }

        $subId = $_GET['id'] ?? 0;
        $formId = $_GET['form_id'] ?? 0;
        $db = Database::getInstance();

        $submission = $db->query("SELECT * FROM pa_submissions WHERE id = :id", ['id' => $subId])->fetch();
        if ($submission) {
            $files = json_decode($submission['files_json'], true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    $filePath = __DIR__ . '/../../public/uploads/secure/' . $file['storage_name'];
                    if (file_exists($filePath)) unlink($filePath);
                }
            }
            $db->query("DELETE FROM pa_submissions WHERE id = :id", ['id' => $subId]);
        }
        header("Location: /admin/forms/submissions?id=" . $formId);
        exit;
    }

    public function downloadFile() {
        Session::init();
        if (!Session::isLoggedIn()) die("Access Denied");
        $file = $_GET['file'] ?? '';

        if (strpos($file, '..') !== false || strpos($file, '/') !== false) {
            die("Invalid filename");
        }

        $path = __DIR__ . '/../../public/uploads/secure/' . $file;
        if (!file_exists($path)) die("File not found");

        $encryptedContent = file_get_contents($path);
        $vault = new \CMS\Core\Vault();
        $decryptedContent = $vault->decrypt($encryptedContent);

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

    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Method not allowed");

        \CMS\Core\Session::init();
        $userId = \CMS\Core\Session::get('user_id') ?: null;

        $formId = $_POST['form_id'];
        $submissionId = $_POST['submission_id'] ?? null;
        $formData = $_POST['data'] ?? [];
        $files = $_FILES['files'] ?? [];

        $service = new \CMS\Services\FormSubmissionService();
        $service->handleSubmission($formId, $submissionId, $formData, $files, $userId);

        // NAPRAWA PRZEKIEROWANIA:
        // Pobieramy url z ukrytego pola (dodanego w BlockRenderer) lub awaryjnie z HTTP_REFERER
        $redirect = $_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
        
        // Parsujemy URL, by bezpiecznie podmienić/dodać zapytania GET nie niszcząc np. ?id=19
        $parsedUrl = parse_url($redirect);
        $path = $parsedUrl['path'] ?? '/';
        $query = $parsedUrl['query'] ?? '';
        
        parse_str($query, $queryParams);
        $queryParams['submitted'] = $formId; // Flaga sukcesu przypisana do tego formularza
        unset($queryParams['edit']); // Zabezpieczenie usuwające tryb edycji z paska URL
        
        $newQuery = http_build_query($queryParams);
        $newRedirect = $path . '?' . $newQuery;
        
        header("Location: " . $newRedirect);
        exit;
    }
}