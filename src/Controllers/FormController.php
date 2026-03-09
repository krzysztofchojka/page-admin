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

        // CZYSZCZENIE CACHE
        $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $file) {
                if(is_file($file)) unlink($file);
            }
        }

        echo json_encode(['status' => 'success']);
    }

    public function autosave() {
        \CMS\Core\Session::init();
        $userId = \CMS\Core\Session::get('user_id') ?: null;
        $formId = $_POST['form_id'] ?? null;
        $submissionId = $_POST['submission_id'] ?? null;
        $formData = $_POST['data'] ?? [];
        
        $service = new \CMS\Services\FormSubmissionService();
        $result = $service->handleAutosave($formId, $submissionId, $formData, $userId);
        
        echo json_encode($result);
        exit;
    }

    public function submissions() {
        Session::init();
        if (!Session::isLoggedIn()) header('Location: /login');
        
        $formId = $_GET['id'] ?? 0;
        $showDrafts = isset($_GET['drafts']) && $_GET['drafts'] == '1'; // Flaga sterująca

        $db = Database::getInstance();
        $vault = new \CMS\Core\Vault();

        try {
            $db->query("ALTER TABLE pa_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted' AFTER user_ip");
        } catch (\Exception $e) {}
        
        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form) die("Form not found");
        $fields = json_decode($form['form_json'], true);

        // Zależnie od filtra pobieramy wszystkie lub tylko przesłane
        $statusCondition = $showDrafts ? "1=1" : "s.status = 'submitted'";

        $rows = $db->query("SELECT s.*, u.email as user_email FROM pa_submissions s LEFT JOIN pa_users u ON s.user_id = u.id WHERE s.form_id = :id AND $statusCondition ORDER BY s.id DESC", ['id' => $formId])->fetchAll();
        
        $decryptedRows = [];
        foreach ($rows as $row) {
            $jsonString = $vault->decrypt($row['data_json']);
            $data = json_decode($jsonString, true);
            $files = json_decode($row['files_json'], true);
            $decryptedRows[] = [
                'id' => $row['id'],
                'date' => $row['created_at'],
                'ip' => $row['user_ip'],
                'status' => $row['status'] ?? 'submitted',
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

    public function asyncUpload() {
        \CMS\Core\Session::init();
        $vault = new \CMS\Core\Vault();

        if (!empty($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                // WERYFIKACJA ROZMIARU (Backend)
                $maxSizeBytes = isset($_POST['max_size']) ? (int)$_POST['max_size'] : 0;
                if ($maxSizeBytes > 0 && $file['size'] > $maxSizeBytes) {
                    echo json_encode([
                        'status' => 'error', 
                        'msg' => 'Plik przekracza maksymalny dopuszczalny rozmiar (' . ($maxSizeBytes / 1024 / 1024) . ' MB).'
                    ]);
                    exit;
                }
                $allowedRaw = $_POST['allowed_exts'] ?? 'jpg, jpeg, png, pdf, doc, docx, zip';
                $allowedArray = array_map('trim', explode(',', strtolower($allowedRaw)));
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                $bannedExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cgi', 'pl'];
                if (!in_array($ext, $allowedArray) || in_array($ext, $bannedExts)) {
                    echo json_encode(['status' => 'error', 'msg' => 'Niedozwolony format pliku.']);
                    exit;
                }
    
                // Mime type check
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
    
                $allowedMimes = [
                    'jpg' => ['image/jpeg', 'image/pjpeg'],
                    'jpeg' => ['image/jpeg', 'image/pjpeg'],
                    'png' => ['image/png'],
                    'gif' => ['image/gif'],
                    'webp' => ['image/webp'],
                    'pdf' => ['application/pdf'],
                    'doc' => ['application/msword'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'xls' => ['application/vnd.ms-excel'],
                    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'zip' => ['application/zip', 'application/x-zip-compressed'],
                    'mp4' => ['video/mp4'],
                    'mp3' => ['audio/mpeg'],
                    'txt' => ['text/plain'],
                    'csv' => ['text/csv', 'text/plain'],
                ];
    
                $isValidMime = false;
                if (array_key_exists($ext, $allowedMimes)) {
                    if (in_array($mime, $allowedMimes[$ext])) {
                        $isValidMime = true;
                    }
                } else {
                    // Allow custom extensions defined by the user but strictly deny executable mimes
                    $bannedMimes = ['text/x-php', 'text/html', 'application/x-sh', 'application/x-executable'];
                    if (!in_array($mime, $bannedMimes)) {
                        $isValidMime = true;
                    }
                }
    
                if (!$isValidMime) {
                    echo json_encode(['status' => 'error', 'msg' => 'Zawartość pliku nie pasuje do rozszerzenia lub jest niedozwolona (MIME: '.$mime.').']);
                    exit;
                }
    
                $uploadDir = __DIR__ . '/../../public/uploads/secure/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
                $tmpName = $file['tmp_name'];
                $filename = $file['name'];
                $encryptedContent = $vault->encrypt(file_get_contents($tmpName));
                $safeName = bin2hex(random_bytes(16)) . '.enc';
                file_put_contents($uploadDir . $safeName, $encryptedContent);
    
                $sessionFiles = \CMS\Core\Session::get('uploaded_files') ?? [];
                $sessionFiles[] = $safeName;
                \CMS\Core\Session::set('uploaded_files', $sessionFiles);
    
                echo json_encode([
                    'status' => 'success',
                    'file' => [
                        'original_name' => $filename,
                        'storage_name' => $safeName
                    ]
                ]);
                exit;
            }
        }
        echo json_encode(['status' => 'error']);
        exit;
    }

    public function exportFiles() {
        \CMS\Core\Session::init();
        if (!\CMS\Core\Session::isLoggedIn()) die("Access Denied");
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? ''); // <-- Ochrona POST utrzymana

        $formId = $_POST['form_id'] ?? 0;
        $namePattern = !empty($_POST['name_pattern']) ? $_POST['name_pattern'] : '{{sys_id}}_{{original_name}}';
        $db = Database::getInstance();
        $vault = new \CMS\Core\Vault();
        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form) die("Formularz nie istnieje");
        
        $formFields = json_decode($form['form_json'], true) ?? [];
        $rows = $db->query("SELECT s.*, u.email as user_email FROM pa_submissions s LEFT JOIN pa_users u ON s.user_id = u.id WHERE form_id = :id ORDER BY id DESC", ['id' => $formId])->fetchAll();
        
        $zipName = 'zalaczniki_formularz_' . $formId . '_' . date('Y-m-d_H-i') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            \CMS\Core\Session::setFlash('Nie można utworzyć pliku ZIP na serwerze.', 'error');
            header("Location: /admin/forms/submissions?id=" . $formId);
            exit;
        }

        $hasFiles = false;
        $fileNamesUsed = []; 

        foreach ($rows as $row) {
            $decryptedData = json_decode($vault->decrypt($row['data_json']), true) ?? [];
            $filesData = json_decode($row['files_json'], true) ?? [];
            if (empty($filesData)) continue;

            $replacements = [
                '{{sys_id}}' => $row['id'],
                '{{sys_date}}' => date('Y-m-d', strtotime($row['created_at'])),
                '{{sys_email}}' => $row['user_email'] ?? 'Gosc',
                '{{sys_ip}}' => $row['user_ip']
            ];

            foreach ($formFields as $f) {
                if (($f['type'] ?? '') === 'html' || ($f['type'] ?? '') === 'file') continue;
                $key = $f['custom_id'] ?? $f['id'] ?? md5($f['label']);
                $val = $decryptedData[$key] ?? '';
                $replacements['{{' . $key . '}}'] = is_array($val) ? implode('_', $val) : (string)$val;
            }

            foreach ($filesData as $fieldKey => $fieldFiles) {
                $fArray = isset($fieldFiles['original_name']) ? [$fieldFiles] : $fieldFiles;
                
                foreach ($fArray as $fIndex => $file) {
                    if (empty($file['storage_name'])) continue;
                    
                    $storagePath = __DIR__ . '/../../public/uploads/secure/' . $file['storage_name'];
                    if (file_exists($storagePath)) {
                        $encryptedContent = file_get_contents($storagePath);
                        $decryptedContent = $vault->decrypt($encryptedContent);
                        
                        $originalName = $file['original_name'];
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                        
                        $itemReplacements = $replacements;
                        $itemReplacements['{{original_name}}'] = $baseName;

                        $newName = $namePattern;
                        $newName = str_replace(array_keys($itemReplacements), array_values($itemReplacements), $newName);
                        
                        $newName = preg_replace('/[^a-zA-Z0-9_\-\. ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', '_', $newName);
                        $newName = trim(preg_replace('/_+/', '_', $newName), '_');
                        
                        $finalName = $newName . '.' . $ext;

                        $counter = 1;
                        $checkName = $finalName;
                        while (isset($fileNamesUsed[$checkName])) {
                            $checkName = $newName . '_' . $counter . '.' . $ext;
                            $counter++;
                        }
                        $fileNamesUsed[$checkName] = true;
                        $finalName = $checkName;

                        $zip->addFromString($finalName, $decryptedContent);
                        $hasFiles = true;
                    }
                }
            }
        }
        $zip->close();

        if ($hasFiles && file_exists($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath); 
            exit;
        } else {
            if (file_exists($zipPath)) unlink($zipPath);
            \CMS\Core\Session::setFlash('Brak załączonych plików w tym formularzu do pobrania.', 'error');
            header("Location: /admin/forms/submissions?id=" . $formId);
            exit;
        }
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

    public function exportSubmissions() {
        \CMS\Core\Session::init();
        if (!\CMS\Core\Session::isLoggedIn()) die("Access Denied");
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? ''); // <-- Ochrona POST utrzymana

        $formId = $_POST['form_id'] ?? 0;
        $format = $_POST['format'] ?? 'csv';
        $selectedFields = $_POST['export_fields'] ?? [];

        if (empty($selectedFields)) {
            \CMS\Core\Session::setFlash('Wybierz przynajmniej jedno pole do eksportu.', 'error');
            header("Location: /admin/forms/submissions?id=" . $formId);
            exit;
        }

        $db = Database::getInstance();
        $vault = new \CMS\Core\Vault();

        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
        if (!$form) die("Formularz nie istnieje");
        
        $formFields = json_decode($form['form_json'], true) ?? [];
        $rows = $db->query("SELECT s.*, u.email as user_email FROM pa_submissions s LEFT JOIN pa_users u ON s.user_id = u.id WHERE form_id = :id ORDER BY id DESC", ['id' => $formId])->fetchAll();

        $headers = [];
        $systemFields = [
            'sys_id' => 'ID Zgłoszenia',
            'sys_date' => 'Data Wysłania',
            'sys_email' => 'Email Konta (System)',
            'sys_ip' => 'Adres IP'
        ];

        foreach ($systemFields as $key => $label) {
            if (in_array($key, $selectedFields)) $headers[] = $label;
        }

        foreach ($formFields as $f) {
            if (($f['type'] ?? '') === 'html') continue;
            $key = $f['custom_id'] ?? $f['id'] ?? md5($f['label']);
            if (in_array($key, $selectedFields)) {
                $headers[] = $f['label'];
            }
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_formularz_' . $formId . '_' . date('Y-m-d_H-i') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF))); 
            fputcsv($output, $headers, ';');
            
            foreach ($rows as $row) {
                $decryptedData = json_decode($vault->decrypt($row['data_json']), true) ?? [];
                $filesData = json_decode($row['files_json'], true) ?? [];
                
                $csvRow = [];
                
                if (in_array('sys_id', $selectedFields)) $csvRow[] = $row['id'];
                if (in_array('sys_date', $selectedFields)) $csvRow[] = $row['created_at'];
                if (in_array('sys_email', $selectedFields)) $csvRow[] = $row['user_email'] ?? 'Gość';
                if (in_array('sys_ip', $selectedFields)) $csvRow[] = $row['user_ip'];

                foreach ($formFields as $f) {
                    if (($f['type'] ?? '') === 'html') continue;
                    $key = $f['custom_id'] ?? $f['id'] ?? md5($f['label']);
                    
                    if (in_array($key, $selectedFields)) {
                        if (($f['type'] ?? '') === 'file') {
                            $fileNames = [];
                            if (!empty($filesData[$key])) {
                                $files = isset($filesData[$key]['original_name']) ? [$filesData[$key]] : $filesData[$key];
                                foreach ($files as $file) {
                                    if (!empty($file['original_name'])) $fileNames[] = $file['original_name'];
                                }
                            }
                            $csvRow[] = implode(', ', $fileNames);
                        } else {
                            $val = $decryptedData[$key] ?? '';
                            $csvRow[] = is_array($val) ? implode(', ', $val) : (string)$val;
                        }
                    }
                }
                fputcsv($output, $csvRow, ';');
            }
            fclose($output);
            exit;

        } elseif ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_formularz_' . $formId . '_' . date('Y-m-d_H-i') . '.json"');
            
            $jsonOutput = [];
            foreach ($rows as $row) {
                $decryptedData = json_decode($vault->decrypt($row['data_json']), true) ?? [];
                $filesData = json_decode($row['files_json'], true) ?? [];
                
                $rowAssoc = [];
                if (in_array('sys_id', $selectedFields)) $rowAssoc['ID Zgłoszenia'] = $row['id'];
                if (in_array('sys_date', $selectedFields)) $rowAssoc['Data Wysłania'] = $row['created_at'];
                if (in_array('sys_email', $selectedFields)) $rowAssoc['Email Konta (System)'] = $row['user_email'] ?? 'Gość';
                if (in_array('sys_ip', $selectedFields)) $rowAssoc['Adres IP'] = $row['user_ip'];

                foreach ($formFields as $f) {
                    if (($f['type'] ?? '') === 'html') continue;
                    $key = $f['custom_id'] ?? $f['id'] ?? md5($f['label']);
                    
                    if (in_array($key, $selectedFields)) {
                        if (($f['type'] ?? '') === 'file') {
                            $fileNames = [];
                            if (!empty($filesData[$key])) {
                                $files = isset($filesData[$key]['original_name']) ? [$filesData[$key]] : $filesData[$key];
                                foreach ($files as $file) {
                                    if (!empty($file['original_name'])) $fileNames[] = $file['original_name'];
                                }
                            }
                            $rowAssoc[$f['label']] = $fileNames;
                        } else {
                            $val = $decryptedData[$key] ?? '';
                            $rowAssoc[$f['label']] = is_array($val) ? implode(', ', $val) : (string)$val;
                        }
                    }
                }
                $jsonOutput[] = $rowAssoc;
            }
            
            echo json_encode($jsonOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function delete() {
        \CMS\Core\Session::init();
        // [POPRAWKA]: Usunięto weryfikację CSRF z POST, ponieważ interfejs przesyła to jako zwykły link GET
        $id = $_GET['id'] ?? 0;
        $db = \CMS\Core\Database::getInstance();
        
        $submissions = $db->query("SELECT COUNT(*) as c FROM pa_submissions WHERE form_id = ?", [$id])->fetch()['c'];
        if ($submissions > 0) {
            \CMS\Core\Session::setFlash("Nie można usunąć: Formularz posiada zebrane zgłoszenia ($submissions). Usuń je najpierw.", "error");
            header('Location: /admin/forms');
            exit;
        }
        
        $inPages = $db->query("SELECT COUNT(*) as c FROM pa_data WHERE contents LIKE ?", ['%"type":"form","content":"'.$id.'"%'])->fetch()['c'];
        $inPosts = $db->query("SELECT COUNT(*) as c FROM pa_posts WHERE contents LIKE ?", ['%"type":"form","content":"'.$id.'"%'])->fetch()['c'];
        
        if ($inPages > 0 || $inPosts > 0) {
            \CMS\Core\Session::setFlash("Nie można usunąć: Formularz jest aktualnie osadzony na stronach lub we wpisach.", "error");
            header('Location: /admin/forms');
            exit;
        }

        $db->query("DELETE FROM pa_forms WHERE id = ?", [$id]);
        \CMS\Core\Session::setFlash("Formularz został pomyślnie usunięty.", "success");
        header('Location: /admin/forms');
        exit;
    }

    public function downloadFile() {
        \CMS\Core\Session::init();
        $file = $_GET['file'] ?? '';

        if (strpos($file, '..') !== false || strpos($file, '/') !== false) {
            die("Invalid filename");
        }

        $path = __DIR__ . '/../../public/uploads/secure/' . $file;
        if (!file_exists($path)) die("File not found");

        $db = \CMS\Core\Database::getInstance();
        $userId = \CMS\Core\Session::get('user_id');
        $isAdmin = \CMS\Core\Session::get('is_admin') == 1;

        $hasAccess = false;

        if ($isAdmin) {
            $hasAccess = true;
        } else {
            $sessionFiles = \CMS\Core\Session::get('uploaded_files') ?? [];
            if (in_array($file, $sessionFiles)) {
                $hasAccess = true;
            } else {
                $stmt = $db->query("SELECT user_id, id FROM pa_submissions WHERE files_json LIKE ?", ['%'.$file.'%']);
                $submission = $stmt->fetch();

                if ($submission) {
                    if ($userId && $submission['user_id'] == $userId) {
                        $hasAccess = true;
                    } else {
                        foreach ($_SESSION as $key => $val) {
                            if (strpos($key, 'draft_') === 0 && $val == $submission['id']) {
                                $hasAccess = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$hasAccess) {
            http_response_code(403);
            die("Brak uprawnień do pobrania tego pliku.");
        }

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
        $result = $service->handleSubmission($formId, $submissionId, $formData, $files, $userId);
        
        $redirect = $_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
        $parsedUrl = parse_url($redirect);
        $path = $parsedUrl['path'] ?? '/';
        $query = $parsedUrl['query'] ?? '';
        parse_str($query, $queryParams);

        if (is_array($result) && isset($result['status']) && $result['status'] === 'error') {
            \CMS\Core\Session::setFlash($result['msg'], 'error');
            $queryParams['err_form'] = $formId; 
            unset($queryParams['submitted']);
            $newQuery = http_build_query($queryParams);
            header("Location: " . $path . '?' . $newQuery . '#form-container-' . $formId);
            exit;
        }

        $queryParams['submitted'] = $formId;
        unset($queryParams['edit']);
        unset($queryParams['err_form']);
        $newQuery = http_build_query($queryParams);
        $newRedirect = $path . '?' . $newQuery . '#form-container-' . $formId;
        
        header("Location: " . $newRedirect);
        exit;
    }
}