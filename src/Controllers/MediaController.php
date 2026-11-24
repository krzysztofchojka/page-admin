<?php
namespace CMS\Controllers;

use CMS\Core\Session;

class MediaController {
    private string $baseDir;
    private string $baseUrl;

    public function __construct() {
        // Używamy ścieżki bez realpath na starcie, by uniknąć problemów na shared hostingu
        $this->baseDir = __DIR__ . '/../../public/uploads/media';
        $this->baseUrl = '/uploads/media';

        // AUTOMATYCZNE TWORZENIE FOLDERU (InfinityFree czasem nie kopiuje pustych folderów)
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
        $this->ensureUploadGuard();
    }

    // Rozszerzenia, które serwer mógłby wykonać – nigdy nie trafiają do publicznego katalogu mediów
    private const BLOCKED_EXT = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'pgif', 'inc',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'jsp', 'jspx', 'shtml', 'htaccess', 'htpasswd',
    ];

    // Pliki edytowalne w edytorze tekstowym (rozszerzenie -> tryb podświetlania składni Ace)
    private const TEXT_EXT = [
        'txt' => 'text', 'md' => 'markdown', 'markdown' => 'markdown', 'html' => 'html', 'htm' => 'html',
        'css' => 'css', 'scss' => 'scss', 'less' => 'less', 'js' => 'javascript', 'mjs' => 'javascript',
        'ts' => 'typescript', 'json' => 'json', 'xml' => 'xml', 'svg' => 'svg', 'csv' => 'text', 'tsv' => 'text',
        'sql' => 'sql', 'yml' => 'yaml', 'yaml' => 'yaml', 'ini' => 'ini', 'conf' => 'text', 'log' => 'text',
        'vtt' => 'text', 'srt' => 'text', 'webmanifest' => 'json', 'map' => 'json',
        'php' => 'php', 'phtml' => 'php', // tylko podgląd – zapis blokuje isAllowedName()
    ];
    private const TEXT_MAX_BYTES = 2 * 1024 * 1024;

    // Limity rozpakowywania (ochrona przed "zip bombami")
    private const UNZIP_MAX_FILES = 5000;
    private const UNZIP_MAX_BYTES = 512 * 1024 * 1024; // 512 MB po rozpakowaniu

    /**
     * Czy nazwę pliku można bezpiecznie zapisać w mediach?
     * Odrzuca pliki ukryte (.htaccess, .user.ini) i każdą nazwę, w której
     * KTÓRAKOLWIEK część rozszerzenia jest wykonywalna (np. "plik.php.jpg").
     */
    private function isAllowedName(string $name): bool {
        if ($name === '' || $name[0] === '.' || strlen($name) > 255) return false;
        if (preg_match('#[\x00-\x1f\\\\/:*?"<>|]#', $name)) return false;
        $parts = explode('.', strtolower($name));
        array_shift($parts);
        foreach ($parts as $ext) {
            if (in_array(trim($ext), self::BLOCKED_EXT, true)) return false;
        }
        return true;
    }

    /**
     * Zamienia ścieżkę względną z przeglądarki/ZIP-a ("folder/pod/plik.jpg") na bezpieczną listę
     * segmentów. Zwraca null przy próbie wyjścia poza katalog (.., ścieżki absolutne, pliki ukryte).
     */
    /** Czy plik da się otworzyć jako tekst? Rozszerzenie z listy albo brak bajtów binarnych w nagłówku. */
    private function isTextFile(string $path): bool {
        if (!is_file($path) || filesize($path) > self::TEXT_MAX_BYTES) return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (isset(self::TEXT_EXT[$ext])) return true;
        $known = ['jpg','jpeg','png','gif','webp','avif','ico','bmp','tif','tiff','pdf','zip','gz','rar','7z','mp3','mp4','mov','webm','ogg','wav','woff','woff2','ttf','otf','eot','doc','docx','xls','xlsx','ppt','pptx'];
        if (in_array($ext, $known, true)) return false;
        $head = (string)@file_get_contents($path, false, null, 0, 8192);
        return $head === '' || (strpos($head, "\0") === false && mb_check_encoding($head, 'UTF-8'));
    }

    private function aceMode(string $name): string {
        return self::TEXT_EXT[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? 'text';
    }

    /** Nazwa folderu: bez ukrytych, "..", znaków specjalnych i separatorów. */
    private function isAllowedDirName(string $name): bool {
        return $name !== '' && $name[0] !== '.' && $name !== '__MACOSX' && strlen($name) <= 255
            && !preg_match('#[\x00-\x1f\\\\/:*?"<>|]#', $name);
    }

    private function sanitizeRelPath(string $rel): ?array {
        $rel = str_replace('\\', '/', $rel);
        if ($rel === '' || $rel[0] === '/' || preg_match('/^[a-z]:/i', $rel)) return null;
        $segments = array_values(array_filter(explode('/', $rel), fn($x) => $x !== ''));
        if (!$segments) return null;
        $file = array_pop($segments);
        foreach ($segments as $dir) {
            if (!$this->isAllowedDirName($dir)) return null;
        }
        if (!$this->isAllowedName($file)) return null;
        $segments[] = $file;
        return $segments;
    }

    /** Tworzy podkatalogi i sprawdza, że wynik nadal leży w katalogu mediów. */
    private function ensureDirInside(string $baseDir, array $dirSegments): ?string {
        $dir = $baseDir;
        foreach ($dirSegments as $seg) {
            $dir .= '/' . $seg;
            if (!is_dir($dir) && !mkdir($dir, 0755) && !is_dir($dir)) return null;
        }
        $real = realpath($dir);
        $root = realpath($this->baseDir);
        return ($real && ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR))) ? $real : null;
    }

    /** .htaccess blokujący wykonywanie skryptów w katalogu mediów (tworzony automatycznie). */
    private function ensureUploadGuard(): void {
        $guard = $this->baseDir . '/.htaccess';
        if (is_file($guard)) return;
        @file_put_contents($guard, "# Wygenerowane przez CMS – blokada wykonywania skryptów w mediach\n"
            . "<FilesMatch \"\\.(php[0-9]?|phtml|pht|phar|phps|cgi|pl|py|rb|sh|asp|aspx|jsp|shtml)$\">\n"
            . "    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n"
            . "</FilesMatch>\n"
            . "<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n");
    }

    private function checkAuth() {
        Session::init();
        if (!Session::isLoggedIn()) {
            http_response_code(403);
            die("Access Denied");
        }
    }

    private function resolvePath(string $relative = '') {
        $path = __DIR__ . '/../../public/uploads/media/' . $relative;
        $realPath = realpath($path);
        
        // Zabezpieczenie: czy użytkownik nie próbuje wyjść poza folder media
        if ($realPath && strpos($realPath, realpath($this->baseDir)) === 0) {
            return $realPath;
        }
        return realpath($this->baseDir);
    }

    public function index() {
        $this->checkAuth();
        $relativePath = $_GET['path'] ?? '';
        $search = $_GET['search'] ?? '';

        $currentDir = $this->resolvePath($relativePath);
        $files = [];

        if (is_dir($currentDir)) {
            $items = scandir($currentDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item[0] === '.') continue;
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
                    $mime = 'application/octet-stream';
                    if (function_exists('mime_content_type')) {
                        $mime = @mime_content_type($fullPath);
                    }
                    
                    // FALLBACK: Jeśli system nie rozpoznał MIME (częsty problem na Windowsie)
                    if (empty($mime) || $mime === 'application/octet-stream' || $mime === false) {
                        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                        $mimes = [
                            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
                            'pdf' => 'application/pdf', 'txt' => 'text/plain', 'html' => 'text/html',
                            'csv' => 'text/csv', 'mp4' => 'video/mp4', 'mp3' => 'audio/mpeg',
                            'zip' => 'application/zip'
                        ];
                        $mime = $mimes[$ext] ?? 'application/octet-stream';
                    }

                    $files[] = [
                        'type' => 'file',
                        'name' => $item,
                        'relative' => $relativeItem,
                        'url' => $this->baseUrl . '/' . $relativeItem,
                        'size' => round(filesize($fullPath)/1024, 2) . ' KB',
                        'mime' => $mime,
                        'editable' => $this->isTextFile($fullPath),
                    ];
                }
            }
        }

        if(isset($_GET["picker"]) && $_GET["picker"]==1){
            require __DIR__ . '/../Views/admin/media/index.php';
        } else {
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
            $saved = 0;
            $skipped = [];
            // relpaths[] – ścieżki względne przy wgrywaniu całych folderów ("galeria/2026/a.jpg")
            $relPaths = isset($_POST['relpaths']) && is_array($_POST['relpaths']) ? $_POST['relpaths'] : [];
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp) {
                $originalName = basename($_FILES['files']['name'][$key]);
                if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) { $skipped[] = $originalName; continue; }

                $dir = $targetDir;
                $name = $originalName;
                if (!empty($relPaths[$key]) && !$isShortcut) {
                    $segments = $this->sanitizeRelPath((string)$relPaths[$key]);
                    if ($segments === null) { $skipped[] = (string)$relPaths[$key]; continue; }
                    $name = array_pop($segments);
                    $dir = $this->ensureDirInside($targetDir, $segments);
                    if ($dir === null) { $skipped[] = (string)$relPaths[$key]; continue; }
                }
                if (!$this->isAllowedName($name)) { $skipped[] = $name; continue; }

                // Zmiana nazwy (dodanie timestampu) dla szybkich uploadów
                if ($isShortcut) {
                    $info = pathinfo($name);
                    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
                    $name = $info['filename'] . '_' . time() . $ext;
                }

                if (move_uploaded_file($tmp, $dir . '/' . $name)) {
                    $lastFile = $name;
                    $saved++;
                }
            }
            echo json_encode(['success' => $saved > 0 || !$skipped, 'saved' => $saved, 'skipped' => $skipped,
                'error' => $saved === 0 && $skipped ? 'Pominięto niedozwolone pliki: ' . implode(', ', array_slice($skipped, 0, 5)) : null,
                'url' => $this->baseUrl . '/' . trim($relativePath . '/' . $lastFile, '/')]);
        } 
        // Case 2: Single file (sent as 'file' - used by our Page Builder)
        else if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $originalName = basename($file['name']);
                $name = $originalName;
                if (!$this->isAllowedName($name)) {
                    echo json_encode(['success' => false, 'error' => 'Niedozwolony typ pliku']);
                    return;
                }
                
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

        if (!$name || !$this->isAllowedDirName($name)) {
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
        $nameOk = is_dir($oldPath) ? $this->isAllowedDirName($new) : $this->isAllowedName($new);
        if ($oldPath === realpath($this->baseDir) || !$nameOk) {
            echo json_encode(['success' => false, 'error' => 'Niedozwolona nazwa']);
            return;
        }
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

    /**
     * Rozpakowuje ZIP z mediów do nowego folderu obok archiwum (nazwa = nazwa ZIP-a).
     * Każdy wpis jest sprawdzany: brak wyjścia poza katalog (zip slip), brak plików
     * wykonywalnych i ukrytych, limity liczby plików i rozmiaru (zip bomb).
     */
    public function unzip()
    {
        $this->checkAuth();
        Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        header('Content-Type: application/json');

        if (!class_exists(\ZipArchive::class)) {
            echo json_encode(['success' => false, 'error' => 'Serwer nie ma rozszerzenia PHP zip.']);
            return;
        }

        $zipPath = $this->resolvePath($_POST['file'] ?? '');
        if (!is_file($zipPath) || strtolower(pathinfo($zipPath, PATHINFO_EXTENSION)) !== 'zip') {
            echo json_encode(['success' => false, 'error' => 'To nie jest plik ZIP.']);
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            echo json_encode(['success' => false, 'error' => 'Nie można otworzyć archiwum (uszkodzone?).']);
            return;
        }
        if ($zip->numFiles > self::UNZIP_MAX_FILES) {
            $zip->close();
            echo json_encode(['success' => false, 'error' => 'Archiwum ma zbyt wiele plików (limit ' . self::UNZIP_MAX_FILES . ').']);
            return;
        }

        // Folder docelowy: "nazwa", a gdy istnieje – "nazwa (2)", "nazwa (3)"...
        $baseName = preg_replace('#[\x00-\x1f\\\\/:*?"<>|]#', '_', pathinfo($zipPath, PATHINFO_FILENAME));
        $baseName = ltrim($baseName, '.') ?: 'archiwum';
        $parent = dirname($zipPath);
        $folder = $baseName;
        for ($i = 2; file_exists($parent . '/' . $folder); $i++) $folder = $baseName . " ($i)";
        $dest = $this->ensureDirInside($parent, [$folder]);
        if ($dest === null) {
            $zip->close();
            echo json_encode(['success' => false, 'error' => 'Nie można utworzyć folderu docelowego.']);
            return;
        }

        $extracted = 0; $skipped = []; $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) continue;
            $entry = $stat['name'];
            if (str_ends_with($entry, '/')) continue;                 // katalog – tworzony przy plikach
            if (preg_match('#(^|/)__MACOSX/#', $entry) || preg_match('#(^|/)\.DS_Store$#', $entry)) continue;

            $segments = $this->sanitizeRelPath($entry);
            if ($segments === null) { $skipped[] = $entry; continue; }

            $total += (int)$stat['size'];
            if ($total > self::UNZIP_MAX_BYTES) { $skipped[] = '(przekroczono limit rozmiaru – przerwano)'; break; }

            $name = array_pop($segments);
            $dir = $this->ensureDirInside($dest, $segments);
            if ($dir === null) { $skipped[] = $entry; continue; }

            // Strumieniowo, z kontrolą rzeczywistej liczby bajtów (rozmiar w nagłówku może kłamać)
            $in = $zip->getStream($entry);
            $out = $in ? fopen($dir . '/' . $name, 'wb') : false;
            if (!$in || !$out) { $skipped[] = $entry; continue; }
            $written = 0; $tooBig = false;
            while (!feof($in)) {
                $chunk = fread($in, 65536);
                if ($chunk === false) break;
                $written += strlen($chunk);
                if ($written > (int)$stat['size'] + 1024 || $total - (int)$stat['size'] + $written > self::UNZIP_MAX_BYTES) { $tooBig = true; break; }
                fwrite($out, $chunk);
            }
            fclose($in); fclose($out);
            if ($tooBig) { @unlink($dir . '/' . $name); $skipped[] = $entry . ' (rozmiar niezgodny z nagłówkiem)'; break; }
            $extracted++;
        }
        $zip->close();

        $rel = trim(substr($dest, strlen(realpath($this->baseDir))), '/\\');
        echo json_encode(['success' => true, 'folder' => $rel, 'extracted' => $extracted, 'skipped' => array_slice($skipped, 0, 20), 'skippedCount' => count($skipped)]);
    }

    /** Odczyt pliku tekstowego do edytora (JSON). */
    public function readText()
    {
        $this->checkAuth();
        header('Content-Type: application/json');
        $path = $this->resolvePath($_GET['file'] ?? '');
        if (!is_file($path) || !$this->isTextFile($path)) {
            echo json_encode(['success' => false, 'error' => 'To nie jest plik tekstowy (lub jest większy niż 2 MB).']);
            return;
        }
        $content = (string)file_get_contents($path);
        if (!mb_check_encoding($content, 'UTF-8')) {
            // starsze pliki w kodowaniu Windows-1250 / ISO-8859-2 – konwertujemy do edycji
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1250');
        }
        $name = basename($path);
        echo json_encode([
            'success' => true,
            'content' => $content,
            'mode' => $this->aceMode($name),
            'readonly' => !$this->isAllowedName($name),
            'mtime' => filemtime($path),
        ]);
    }

    /** Zapis pliku tekstowego z edytora (także tworzenie nowego pliku). */
    public function saveText()
    {
        $this->checkAuth();
        Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        header('Content-Type: application/json');

        $content = (string)($_POST['content'] ?? '');
        if (strlen($content) > self::TEXT_MAX_BYTES) {
            echo json_encode(['success' => false, 'error' => 'Plik jest za duży (limit 2 MB).']);
            return;
        }

        if (!empty($_POST['create'])) {
            // Nowy plik w bieżącym folderze
            $dir = $this->resolvePath($_POST['path'] ?? '');
            $name = trim((string)($_POST['name'] ?? ''));
            if (!$this->isAllowedName($name) || !isset(self::TEXT_EXT[strtolower(pathinfo($name, PATHINFO_EXTENSION))])) {
                echo json_encode(['success' => false, 'error' => 'Niedozwolona nazwa lub rozszerzenie (np. notatka.txt, style.css, dane.json).']);
                return;
            }
            $target = $dir . '/' . $name;
            if (file_exists($target)) {
                echo json_encode(['success' => false, 'error' => 'Plik o tej nazwie już istnieje.']);
                return;
            }
        } else {
            $target = $this->resolvePath($_POST['file'] ?? '');
            if (!is_file($target) || !$this->isTextFile($target)) {
                echo json_encode(['success' => false, 'error' => 'Nie można edytować tego pliku.']);
                return;
            }
            if (!$this->isAllowedName(basename($target))) {
                echo json_encode(['success' => false, 'error' => 'Pliki wykonywalne (np. .php) są w mediach tylko do odczytu.']);
                return;
            }
            // Ochrona przed nadpisaniem zmian zrobionych w międzyczasie (inna karta / inna osoba)
            if (!empty($_POST['mtime']) && empty($_POST['force']) && filemtime($target) > (int)$_POST['mtime']) {
                echo json_encode(['success' => false, 'conflict' => true, 'error' => 'Plik został zmieniony w międzyczasie. Zapisać mimo to?']);
                return;
            }
        }

        // Zapis atomowy: plik tymczasowy w tym samym katalogu + rename
        $tmp = dirname($target) . '/.tmp_' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $content) === false || !rename($tmp, $target)) {
            @unlink($tmp);
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu na serwerze (uprawnienia?).']);
            return;
        }
        clearstatcache(true, $target);
        $rel = trim(substr(realpath($target), strlen(realpath($this->baseDir))), '/\\');
        echo json_encode(['success' => true, 'file' => $rel, 'mtime' => filemtime($target)]);
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
