<?php
namespace CMS\Controllers;

use CMS\Core\Session;

class MediaController {

    private string $baseDir;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseDir = realpath(__DIR__ . '/../../public/uploads/media');
        $this->baseUrl = '/uploads/media';
    }

    private function checkAuth()
    {
        Session::init();
        if (!Session::isLoggedIn()) {
            http_response_code(403);
            die("Access Denied");
        }
    }

    private function resolvePath(string $relative = '')
    {
        $path = realpath($this->baseDir . '/' . $relative);
        if (!$path || strpos($path, $this->baseDir) !== 0) {
            return $this->baseDir;
        }
        return $path;
    }

    public function index()
    {
        $this->checkAuth();

        $relativePath = $_GET['path'] ?? '';
        $search = $_GET['search'] ?? '';

        $currentDir = $this->resolvePath($relativePath);

        $files = [];

        foreach (scandir($currentDir) as $item) {

            if ($item === '.' || $item === '..') continue;
            if ($search && stripos($item, $search) === false) continue;

            $fullPath = $currentDir . '/' . $item;
            $relativeItem = trim($relativePath . '/' . $item, '/');

            if (is_dir($fullPath)) {
                $files[] = [
                    'type' => 'folder',
                    'name' => $item,
                    'path' => $relativeItem
                ];
            } else {
                $files[] = [
                    'type' => 'file',
                    'name' => $item,
                    'relative' => $relativeItem,
                    'url' => $this->baseUrl . '/' . $relativeItem,
                    'size' => round(filesize($fullPath)/1024, 2) . ' KB',
                    'mime' => mime_content_type($fullPath)
                ];
            }
        }
        if(isset($_GET["picker"]) && $_GET["picker"]==1){
            require __DIR__ . '/../Views/admin/media/index.php';
        }else{
            ob_start();
            require __DIR__ . '/../Views/admin/media/index.php';
            $content = ob_get_clean();
            require_once __DIR__ . '/../Views/admin/layout.php';
        }
    }

    public function upload()
    {
        $this->checkAuth();

        $relativePath = $_POST['path'] ?? '';
        
        // --- OBSŁUGA SZYBKIEGO UPLOADU (SHORTCUT / CTRL+V) ---
        $isShortcut = isset($_POST['shortcut']) && $_POST['shortcut'] == '1';
        
        if ($isShortcut) {
            $relativePath = 'shortcut';
            $targetDir = $this->baseDir . '/' . $relativePath;
            // Tworzymy folder, jeśli jeszcze nie istnieje
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        } else {
            $targetDir = $this->resolvePath($relativePath);
        }
        // -----------------------------------------------------

        // Case 1: Multiple files (sent as files[])
        if (isset($_FILES['files']) && is_array($_FILES['files']['tmp_name'])) {
            $lastFile = '';
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalName = basename($_FILES['files']['name'][$key]);
                    $name = $originalName;
                    
                    // Zmiana nazwy (dodanie timestampu) dla szybkich uploadów
                    if ($isShortcut) {
                        $info = pathinfo($originalName);
                        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
                        $name = $info['filename'] . '_' . time() . $ext;
                    }

                    if (move_uploaded_file($tmp, $targetDir . '/' . $name)) {
                        $lastFile = $name;
                    }
                }
            }
            echo json_encode(['success' => true, 'url' => $this->baseUrl . '/' . trim($relativePath . '/' . $lastFile, '/')]);
        } 
        // Case 2: Single file (sent as 'file' - used by our Page Builder)
        else if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $originalName = basename($file['name']);
                $name = $originalName;
                
                // Zmiana nazwy (dodanie timestampu) dla szybkich uploadów
                if ($isShortcut) {
                    $info = pathinfo($originalName);
                    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
                    $name = $info['filename'] . '_' . time() . $ext;
                }

                $relativePathFile = trim($relativePath . '/' . $name, '/');
                
                if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $name)) {
                    echo json_encode([
                        'success' => true, 
                        'url' => $this->baseUrl . '/' . $relativePathFile
                    ]);
                    return;
                }
            }
            echo json_encode(['success' => false, 'error' => 'Move failed']);
        } 
        else {
            echo json_encode(['success' => false, 'error' => 'No files detected in request']);
        }
    }

    public function createFolder()
    {
        $this->checkAuth();
        $path = $_POST['path'] ?? '';
        $name = basename($_POST['name'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'error' => 'Brak nazwy']);
            return;
        }

        $dir = $this->resolvePath($path);
        if (mkdir($dir . '/' . $name)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Nie udało się utworzyć folderu']);
        }
    }

    public function delete()
    {
        $this->checkAuth();

        $relative = $_POST['file'] ?? '';
        $path = $this->resolvePath($relative);

        if (is_dir($path)) {
            $this->deleteRecursive($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
    }

    private function deleteRecursive($dir)
    {
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            $full = $dir . '/' . $item;
            is_dir($full) ? $this->deleteRecursive($full) : unlink($full);
        }
        rmdir($dir);
    }

    public function rename()
    {
        $this->checkAuth();
        $old = $_POST['old'] ?? '';
        $new = basename($_POST['new'] ?? '');

        $oldPath = $this->resolvePath($old);
        $newPath = dirname($oldPath) . '/' . $new;

        if (rename($oldPath, $newPath)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function downloadZip()
    {
        $this->checkAuth();
        $path = $_GET['path'] ?? '';
        $dir = $this->resolvePath($path);

        if (!is_dir($dir)) {
            die("Katalog nie istnieje.");
        }

        // Ustalenie nazwy pliku na podstawie folderu
        $folderName = $path === '' ? 'media_root' : basename($dir);
        $zipName = $folderName . '_' . time() . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            die("Nie można utworzyć pliku ZIP.");
        }

        // Rekurencyjne przeszukiwanie i dodawanie plików do archiwum
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                // Zachowanie struktury katalogów wewnątrz ZIP
                $relativePath = substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // Wymuszenie pobrania
        if (file_exists($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath); // Usunięcie pliku tymczasowego z serwera po pobraniu
            exit;
        } else {
            die("Błąd podczas generowania pliku ZIP.");
        }
    }

    public function move()
    {
        $this->checkAuth();
        $items = json_decode($_POST['items'] ?? '[]', true);
        $target = $_POST['target'] ?? '';
        $targetDir = $this->resolvePath($target);
        
        $successCount = 0;
        foreach($items as $item) {
            $sourcePath = $this->resolvePath($item);
            if (file_exists($sourcePath)) {
                $destPath = $targetDir . '/' . basename($sourcePath);
                if (rename($sourcePath, $destPath)) {
                    $successCount++;
                }
            }
        }
        echo json_encode(['success' => true, 'moved' => $successCount]);
    }
}
