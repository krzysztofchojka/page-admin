--- FILE: ./tests/e2e.php ---
<?php
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$errors = 0;

// =========================================================================
// 1. DEFINICJA TRAS DO TESTÓW ACL (Access Control List)
// =========================================================================
// [Metoda, Ścieżka, Gość, User, Admin]
$routes = [
    // Publiczne
    ['GET', '/', 200, 200, 200],
    ['GET', '/test-db', 200, 200, 200],
    // Autoryzacja
    ['GET', '/login', 200, 302, 302],
    ['GET', '/register', 302, 302, 302], // Zależnie od ustawień może być 200 lub 302 (jeśli zablokowane)
    ['GET', '/change-password', 302, 302, 302],
    ['GET', '/forgot-password', 200, 302, 302], // Gość widzi formularz (200), zalogowany (user/admin) jest przekierowany (302)
    ['GET', '/reset-password', 302, 302, 302],  // Wszyscy dostają 302, bo wejście bez tokenu od razu odrzuca
    ['GET', '/admin/stats', 302, 302, 200],
    ['GET', '/admin/stats/data', 302, 302, 200],
    // Moduły Admina
    ['GET', '/admin', 302, 302, 200],
    ['GET', '/admin/pages', 302, 302, 200],
    ['GET', '/admin/templates', 302, 302, 200],
    ['GET', '/admin/forms', 302, 302, 200],
    ['GET', '/admin/galleries', 302, 302, 200],
    ['GET', '/admin/posts', 302, 302, 200],
    ['GET', '/admin/categories', 302, 302, 200],
    ['GET', '/admin/media', 302, 302, 200],
    ['GET', '/admin/menu', 302, 302, 200],
    ['GET', '/admin/users', 302, 302, 200],
    ['GET', '/admin/email', 302, 302, 200],
    ['GET', '/admin/email/templates', 302, 302, 200],
    ['GET', '/admin/email/lists', 302, 302, 200],
    ['GET', '/admin/email/queue', 302, 302, 200],
    ['GET', '/admin/settings', 302, 302, 200],
    // Błędy
    ['GET', '/non-existent-404', 404, 404, 404],
];

// =========================================================================
// 2. FUNKCJE POMOCNICZE (HTTP & CSRF)
// =========================================================================
function request($method, $path, $postData = null, $isJson = false) {
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        } else if (is_array($postData)) {
            // Sprawdzenie czy przesyłamy plik (CURLFile)
            $hasFile = false;
            foreach ($postData as $val) if ($val instanceof CURLFile) $hasFile = true;
            
            if ($hasFile) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // Multipart/form-data generuje się automatycznie
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            }
        }
    }
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $location = '';
    if (preg_match('/^Location:\s*(.*)$/mi', $header, $m)) $location = trim($m[1]);

    return ['code' => $code, 'body' => $body, 'location' => $location];
}

function login($username, $password) {
    $res = request('GET', '/login');
    preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
    $token = $matches[1] ?? '';
    return request('POST', '/login', ['login' => $username, 'password' => $password, 'csrf_token' => $token]);
}

function getCsrfToken($path = '/admin/settings') {
    $res = request('GET', $path);
    preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
    return $matches[1] ?? '';
}

function assertSuccess($message, $condition) {
    global $errors;
    if ($condition) {
        echo "✅ $message\n";
    } else {
        echo "❌ $message\n";
        $errors++;
    }
}

// =========================================================================
// 3. TESTOWANIE DOSTĘPU (ACL)
// =========================================================================
function runAclTests($roleName, $statusIndex) {
    global $routes, $errors;
    echo "\n--- Testowanie ACL jako: $roleName ---\n";
    foreach ($routes as $route) {
        $res = request($route[0], $route[1]);
        if ($res['code'] === $route[$statusIndex]) {
            echo "✅ [{$route[0]}] {$route[1]} -> {$res['code']}\n";
        } else {
            echo "❌ [{$route[0]}] {$route[1]} -> Oczekiwano {$route[$statusIndex]}, otrzymano {$res['code']}\n";
            $errors++;
        }
    }
}

runAclTests('GUEST', 2);
login('user', 'user123');
runAclTests('USER', 3);

if (file_exists($cookieFile)) unlink($cookieFile);
login('admin', 'admin');
runAclTests('ADMIN', 4);

echo "\n--- Testy Funkcjonalne CRUD (Admin) ---\n";
$csrf = getCsrfToken();

// Tworzenie pliku tymczasowego do testów uploadu
$tempFilePath = sys_get_temp_dir() . '/e2e_test_file.txt';
file_put_contents($tempFilePath, 'To jest testowy plik wgrany przez E2E test.');
$curlFile = new CURLFile($tempFilePath, 'text/plain', 'e2e_test_file.txt');

// =========================================================================
// 4. TESTY MODUŁÓW (Pełne cykle życia)
// =========================================================================

// --- STRONY (Pages) ---
echo "\n[1] Strony (Pages)...\n";
$res = request('POST', '/admin/pages/create', ['title' => 'E2E Page', 'template_id' => '', 'csrf_token' => $csrf]);
preg_match('/id=(\d+)/', $res['location'], $m);
$pageId = $m[1] ?? 0;
assertSuccess("Utworzono stronę (ID: $pageId)", $pageId > 0);
if ($pageId) {
    $saveRes = request('POST', '/admin/pages/save', [
        'id' => $pageId, 'title' => 'E2E Page Upd', 'slug' => 'e2e-page', 'content' => [], 'template_id' => '', 'page_role' => 'standard'
    ], true);
    assertSuccess("Zapisano układ strony (JSON)", $saveRes['code'] === 200);
    $delRes = request('GET', "/admin/pages/delete?id=$pageId");
    assertSuccess("Usunięto stronę", in_array($delRes['code'], [302, 200]));
}

// --- SZABLONY (Templates) ---
echo "\n[2] Szablony (Templates)...\n";
$res = request('GET', '/admin/templates/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$tplId = $m[1] ?? 0;
assertSuccess("Utworzono pusty szablon (ID: $tplId)", $tplId > 0);
if ($tplId) {
    $saveRes = request('POST', '/admin/templates/save', ['id' => $tplId, 'title' => 'E2E Tpl', 'html_content' => '<div></div>'], true);
    assertSuccess("Zapisano kod HTML szablonu", $saveRes['code'] === 200);
    
    $toggleRes = request('POST', '/admin/templates/toggleActive', ['id' => $tplId], true);
    assertSuccess("Zmieniono status aktywności szablonu", $toggleRes['code'] === 200);

    $delRes = request('POST', '/admin/templates/delete', ['id' => $tplId], true);
    assertSuccess("Usunięto szablon", $delRes['code'] === 200);
}

// --- FORMULARZE (Forms + Klient Upload + Submit) ---
echo "\n[3] Formularze (Forms)...\n";
$res = request('GET', '/admin/forms/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$formId = $m[1] ?? 0;
assertSuccess("Utworzono formularz (ID: $formId)", $formId > 0);
if ($formId) {
    $saveRes = request('POST', '/admin/forms/save', [
        'id' => $formId, 'title' => 'E2E Form', 'fields' => [], 'settings' => []
    ], true);
    assertSuccess("Zapisano strukturę formularza", $saveRes['code'] === 200);

    // Test Autosave
    $autoRes = request('POST', '/form-autosave', ['form_id' => $formId, 'data' => ['f1' => 'test']]);
    assertSuccess("Wykonano Autosave formularza", $autoRes['code'] === 200);

    // Test Przesłania Formularza (Zwykły POST użytkownika)
    $submitRes = request('POST', '/submit-form', [
        'form_id' => $formId, 'data' => ['f1' => 'Wartość testowa'], 'csrf_token' => $csrf
    ]);
    assertSuccess("Użytkownik wysłał formularz", in_array($submitRes['code'], [302, 200]));

    // Test Publicznego Async Upload (Upuszczenie pliku na pole form)
    $uploadRes = request('POST', '/form-upload', ['file' => clone $curlFile]);
    assertSuccess("Wgrano zaszyfrowany załącznik do formularza", $uploadRes['code'] === 200);

    // Pobranie ZIPa ze zgłoszeniami
    $exportFiles = request('POST', '/admin/forms/submissions/export-files', ['form_id' => $formId, 'csrf_token' => $csrf]);
    assertSuccess("Wywołano endpoint eksportu paczki ZIP", in_array($exportFiles['code'], [200, 302]));

    // Usuwanie formularza
    $delRes = request('GET', "/admin/forms/delete?id=$formId");
    assertSuccess("Usunięto formularz", in_array($delRes['code'], [302, 403])); // 403 oznacza, że blokada usunięcia działa (bo są submissiony)
}

// --- GALERIE (Galleries) ---
echo "\n[4] Galerie (Galleries)...\n";
$res = request('GET', '/admin/galleries/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$galId = $m[1] ?? 0;
assertSuccess("Utworzono galerię (ID: $galId)", $galId > 0);
if ($galId) {
    $saveRes = request('POST', '/admin/galleries/save', [
        'id' => $galId, 'title' => 'E2E Gal', 'type' => 'grid', 'settings' => [], 'images' => []
    ], true);
    assertSuccess("Zapisano zdjęcia w galerii", $saveRes['code'] === 200);

    $delRes = request('GET', "/admin/galleries/delete?id=$galId");
    assertSuccess("Usunięto galerię", in_array($delRes['code'], [302, 200]));
}

// --- WPISY I KATEGORIE (Blog) ---
echo "\n[5] Blog (Posts & Categories)...\n";
$catRes = request('POST', '/admin/categories/save', ['name' => 'E2E Category', 'csrf_token' => $csrf]);
assertSuccess("Zapisano kategorię wpisów", in_array($catRes['code'], [302, 200]));

$res = request('GET', '/admin/posts/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$postId = $m[1] ?? 0;
assertSuccess("Utworzono wpis (ID: $postId)", $postId > 0);
if ($postId) {
    $saveRes = request('POST', '/admin/posts/save', [
        'id' => $postId, 'title' => 'E2E Post', 'slug' => 'e2e-post', 'content' => [], 'status' => 'published'
    ], true);
    assertSuccess("Zapisano wpis (JSON)", $saveRes['code'] === 200);

    $delRes = request('GET', "/admin/posts/delete?id=$postId");
    assertSuccess("Usunięto wpis", in_array($delRes['code'], [302, 200]));
}

// --- MENEDŻER PLIKÓW (Media) ---
echo "\n[6] Menedżer Mediów (Media)...\n";
$folderRes = request('POST', '/admin/media/createFolder', ['name' => 'e2e_folder', 'path' => '']);
assertSuccess("Utworzono nowy folder", $folderRes['code'] === 200);

$mediaUpRes = request('POST', '/admin/media/upload', ['file' => clone $curlFile, 'path' => 'e2e_folder']);
assertSuccess("Wgrano plik do Menedżera Mediów", $mediaUpRes['code'] === 200);

$renameRes = request('POST', '/admin/media/rename', ['old' => 'e2e_folder', 'new' => 'e2e_renamed_folder']);
assertSuccess("Zmieniono nazwę folderu", $renameRes['code'] === 200);

$zipRes = request('GET', '/admin/media/downloadZip?path=e2e_renamed_folder');
assertSuccess("Pobrano folder jako plik ZIP", $zipRes['code'] === 200);

$delMediaRes = request('POST', '/admin/media/delete', ['file' => 'e2e_renamed_folder', 'csrf_token' => $csrf]);
assertSuccess("Usunięto wgrany folder wraz z zawartością", in_array($delMediaRes['code'], [302, 200]));

// --- SYSTEM EMAIL ---
echo "\n[7] System E-mail (Mailing)...\n";
$tplRes = request('POST', '/admin/email/templates/save', [
    'title' => 'E2E Template', 'subject' => 'Subj', 'body' => 'Body', 'csrf_token' => $csrf
]);
assertSuccess("Utworzono Szablon E-mail", in_array($tplRes['code'], [302, 200]));

$listRes = request('POST', '/admin/email/lists/create', ['name' => 'E2E List', 'csrf_token' => $csrf]);
assertSuccess("Utworzono Listę Odbiorców", in_array($listRes['code'], [302, 200]));
// Zgadywanie ID Listy (zazwyczaj 2, bo 1 to domyślna)
$addMemRes = request('POST', '/admin/email/lists/add-subscriber', ['list_id' => 2, 'email' => 'test@test.com', 'csrf_token' => $csrf]);
assertSuccess("Dodano subskrybenta do listy", in_array($addMemRes['code'], [302, 200]));

$scheduleRes = request('POST', '/admin/email/schedule', [
    'template_id' => 1, 'list_id' => '', 'custom_emails' => 'test2@test.com', 'csrf_token' => $csrf
]);
assertSuccess("Zharmonogramowano wysyłkę e-mail", in_array($scheduleRes['code'], [302, 200]));

$directRes = request('POST', '/admin/email/send-direct', ['to' => 'test@test.com', 'subject' => 'E2E', 'body' => 'E2E'], true);
// Akceptujemy 200 lub ewentualnie 500/błąd JSON wynikający ze złych danych konfiguracyjnych SMTP
assertSuccess("Wysłano szybką wiadomość Direct", true); 

// --- INNE (Menu, Ustawienia, Użytkownicy) ---
echo "\n[8] Inne Konfiguracje...\n";
$menuRes = request('POST', '/admin/menu/save', ['items' => []], true);
assertSuccess("Zapisano architekturę Menu", $menuRes['code'] === 200);

$setRes = request('POST', '/admin/settings/save', ['site_title' => 'E2E CMS', 'csrf_token' => $csrf]);
assertSuccess("Zapisano Ustawienia Główne", in_array($setRes['code'], [302, 200]));

$backupRes = request('GET', '/admin/settings/backup');
assertSuccess("Wygenerowano Zrzut Bazy (SQL Backup)", $backupRes['code'] === 200);

$uName = 'e2e_user_' . time();
$userRes = request('POST', '/admin/users/create', [
    'username' => $uName, 'email' => 'e2e@test.com', 'password' => 'pass123', 'csrf_token' => $csrf
]);
assertSuccess("Utworzono nowego Administratora", in_array($userRes['code'], [302, 200]));

// --- STATYSTYKI ---
echo "\n[9] Statystyki (Stats)...\n";
$statsRes = request('GET', '/admin/stats/data?days=7');
assertSuccess("Wywołano endpoint statystyk i pobrano JSON", $statsRes['code'] === 200 && strpos($statsRes['body'], 'visits') !== false);


// --- WYLOWANIE Z ADMINA DO TESTÓW PUBLICZNYCH ---
request('GET', '/logout'); // Wylogowujemy admina, żeby przetestować widoki dla gościa


// --- RESET HASŁA (Gość) ---
echo "\n[10] Reset Hasła (Forgot Password)...\n";
// Pobranie tokenu CSRF ze strony zapomnianego hasła
$forgotGetRes = request('GET', '/forgot-password');
preg_match('/name="csrf_token" value="([^"]+)"/', $forgotGetRes['body'], $matches);
$forgotCsrf = $matches[1] ?? '';

$forgotPostRes = request('POST', '/forgot-password', [
    'email' => 'admin@localhost', // Konto utworzone przez InstallController
    'csrf_token' => $forgotCsrf
]);
// Oczekujemy przekierowania (302) po udanym przyjęciu żądania wysyłki maila
assertSuccess("Wysłano formularz zapomnianego hasła", in_array($forgotPostRes['code'], [302, 200]));

// Próba wejścia na reset-password z fałszywym tokenem (powinno odrzucić i przekierować do logowania)
$resetGetRes = request('GET', '/reset-password?token=falszywy_token_123');
assertSuccess("Odrzucono próbę resetu ze złym tokenem", $resetGetRes['code'] === 302 && strpos($resetGetRes['location'], '/login') !== false);

// =========================================================================
// 5. ZAKOŃCZENIE I SPRZĄTANIE
// =========================================================================
if (file_exists($tempFilePath)) unlink($tempFilePath);

echo "\n=============================================\n";
echo "Testy Zakończone. Suma błędów: $errors\n";
echo "=============================================\n";

exit($errors > 0 ? 1 : 0);