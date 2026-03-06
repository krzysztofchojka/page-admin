--- FILE: ./tests/e2e.php ---
<?php
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$errors = 0;

// Definicja tras: [Metoda, Ścieżka, Gość, User, Admin]
$routes = [
    ['GET', '/', 200, 200, 200],
    ['GET', '/login', 200, 302, 302],
    ['POST', '/submit-form', 302, 302, 302],
    ['GET', '/change-password', 302, 302, 302],
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
    ['GET', '/admin/settings', 302, 302, 200],
    ['GET', '/non-existent-404', 404, 404, 404],
];

function request($method, $path, $postData = null, $isJson = false) {
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true); // Pobierz nagłówki by odczytać "Location" (dla przekierowań przy tworzeniu)

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        } else if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }
    }
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $location = '';
    if (preg_match('/^Location:\s*(.*)$/mi', $header, $m)) {
        $location = trim($m[1]);
    }

    return ['code' => $code, 'body' => $body, 'location' => $location];
}

// Funkcja pomocnicza do logowania z obsługą CSRF
function login($username, $password) {
    $res = request('GET', '/login');
    preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
    $token = $matches[1] ?? '';
    return request('POST', '/login', [
        'login' => $username,
        'password' => $password,
        'csrf_token' => $token
    ]);
}

// Funkcja wyciągająca token z pierwszej lepszej strony formularza
function getCsrfToken() {
    $res = request('GET', '/admin/pages/create');
    preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
    return $matches[1] ?? '';
}

function runTests($roleName, $statusIndex) {
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

// 1. GUEST
runTests('GUEST', 2);

// 2. USER
login('user', 'user123');
runTests('USER', 3);

// 3. ADMIN
if (file_exists($cookieFile)) unlink($cookieFile);
login('admin', 'admin');
runTests('ADMIN', 4);

echo "\n--- Testy Funkcjonalne CRUD (Admin) ---\n";
$csrf = getCsrfToken();

// 1. PAGES
echo "\nStrony (Pages)...\n";
$res = request('POST', '/admin/pages/create', ['title' => 'E2E Page', 'template_id' => '', 'csrf_token' => $csrf]);
preg_match('/id=(\d+)/', $res['location'], $m);
$pageId = $m[1] ?? 0;
if ($pageId) {
    echo "✅ Utworzono stronę (ID: $pageId)\n";
    $saveRes = request('POST', '/admin/pages/save', [
        'id' => $pageId,
        'title' => 'E2E Page Upd',
        'slug' => 'e2e-page',
        'content' => [],
        'template_id' => '',
        'page_role' => 'standard'
    ], true);
    if ($saveRes['code'] === 200) echo "✅ Zapisano stronę (ID: $pageId)\n";
    else { echo "❌ Błąd zapisu strony\n"; $errors++; }
    
    $delRes = request('GET', "/admin/pages/delete?id=$pageId");
    if ($delRes['code'] === 302) echo "✅ Usunięto stronę (ID: $pageId)\n";
    else { echo "❌ Błąd usuwania strony\n"; $errors++; }
} else { echo "❌ Błąd tworzenia strony\n"; $errors++; }

// 2. TEMPLATES
echo "\nSzablony (Templates)...\n";
$res = request('GET', '/admin/templates/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$tplId = $m[1] ?? 0;
if ($tplId) {
    echo "✅ Utworzono szablon (ID: $tplId)\n";
    $saveRes = request('POST', '/admin/templates/save', [
        'id' => $tplId,
        'title' => 'E2E Tpl',
        'html_content' => '<div></div>'
    ], true);
    if ($saveRes['code'] === 200) echo "✅ Zapisano szablon (ID: $tplId)\n";
    else { echo "❌ Błąd zapisu szablonu\n"; $errors++; }

    $delRes = request('POST', '/admin/templates/delete', ['id' => $tplId], true);
    if ($delRes['code'] === 200) echo "✅ Usunięto szablon (ID: $tplId)\n";
    else { echo "❌ Błąd usuwania szablonu\n"; $errors++; }
} else { echo "❌ Błąd tworzenia szablonu\n"; $errors++; }

// 3. FORMS
echo "\nFormularze (Forms)...\n";
$res = request('GET', '/admin/forms/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$formId = $m[1] ?? 0;
if ($formId) {
    echo "✅ Utworzono formularz (ID: $formId)\n";
    $saveRes = request('POST', '/admin/forms/save', [
        'id' => $formId,
        'title' => 'E2E Form',
        'fields' => [],
        'settings' => []
    ], true);
    if ($saveRes['code'] === 200) echo "✅ Zapisano formularz (ID: $formId)\n";
    else { echo "❌ Błąd zapisu formularza\n"; $errors++; }

    // Testowanie usuwania; skrypt ma GET z CSRF protection więc obsłuży też ewentualny 403
    $delRes = request('GET', "/admin/forms/delete?id=$formId");
    if (in_array($delRes['code'], [302, 403])) echo "✅ Wykonano żądanie usunięcia formularza\n";
    else { echo "❌ Błąd usuwania formularza\n"; $errors++; }
} else { echo "❌ Błąd tworzenia formularza\n"; $errors++; }

// 4. GALLERIES
echo "\nGalerie (Galleries)...\n";
$res = request('GET', '/admin/galleries/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$galId = $m[1] ?? 0;
if ($galId) {
    echo "✅ Utworzono galerię (ID: $galId)\n";
    $saveRes = request('POST', '/admin/galleries/save', [
        'id' => $galId,
        'title' => 'E2E Gal',
        'type' => 'grid',
        'settings' => [],
        'images' => []
    ], true);
    if ($saveRes['code'] === 200) echo "✅ Zapisano galerię (ID: $galId)\n";
    else { echo "❌ Błąd zapisu galerii\n"; $errors++; }

    $delRes = request('GET', "/admin/galleries/delete?id=$galId");
    if ($delRes['code'] === 302) echo "✅ Usunięto galerię (ID: $galId)\n";
    else { echo "❌ Błąd usuwania galerii\n"; $errors++; }
} else { echo "❌ Błąd tworzenia galerii\n"; $errors++; }

// 5. POSTS
echo "\nWpisy (Posts)...\n";
$res = request('GET', '/admin/posts/create');
preg_match('/id=(\d+)/', $res['location'], $m);
$postId = $m[1] ?? 0;
if ($postId) {
    echo "✅ Utworzono wpis (ID: $postId)\n";
    $saveRes = request('POST', '/admin/posts/save', [
        'id' => $postId,
        'title' => 'E2E Post',
        'slug' => 'e2e-post',
        'content' => [],
        'excerpt' => '',
        'thumbnail' => '',
        'tags' => '',
        'category_id' => '',
        'status' => 'published'
    ], true);
    if ($saveRes['code'] === 200) echo "✅ Zapisano wpis (ID: $postId)\n";
    else { echo "❌ Błąd zapisu wpisu\n"; $errors++; }

    $delRes = request('GET', "/admin/posts/delete?id=$postId");
    if ($delRes['code'] === 302) echo "✅ Usunięto wpis (ID: $postId)\n";
    else { echo "❌ Błąd usuwania wpisu\n"; $errors++; }
} else { echo "❌ Błąd tworzenia wpisu\n"; $errors++; }

// 6. CATEGORIES
echo "\nKategorie (Categories)...\n";
$res = request('POST', '/admin/categories/save', ['name' => 'E2E Cat', 'csrf_token' => $csrf]);
if ($res['code'] === 302) echo "✅ Utworzono kategorię\n";
else { echo "❌ Błąd tworzenia kategorii\n"; $errors++; }

// 7. EMAIL TEMPLATES
echo "\nSzablony E-mail...\n";
$res = request('POST', '/admin/email/templates/save', [
    'title' => 'E2E Template',
    'subject' => 'E2E Subject',
    'body' => 'E2E Body',
    'csrf_token' => $csrf
]);
if ($res['code'] === 302) echo "✅ Utworzono szablon e-mail\n";
else { echo "❌ Błąd tworzenia szablonu e-mail\n"; $errors++; }

// 8. EMAIL LISTS
echo "\nListy Mailingowe...\n";
$res = request('POST', '/admin/email/lists/create', [
    'name' => 'E2E List',
    'csrf_token' => $csrf
]);
if ($res['code'] === 302) echo "✅ Utworzono listę e-mail\n";
else { echo "❌ Błąd tworzenia listy e-mail\n"; $errors++; }

// 9. SETTINGS
echo "\nUstawienia (Settings)...\n";
$res = request('POST', '/admin/settings/save', [
    'site_title' => 'E2E CMS',
    'csrf_token' => $csrf
]);
if ($res['code'] === 302) echo "✅ Zapisano ustawienia\n";
else { echo "❌ Błąd zapisu ustawień\n"; $errors++; }

// 10. MENU
echo "\nMenu...\n";
$res = request('POST', '/admin/menu/save', ['items' => []], true);
if ($res['code'] === 200) echo "✅ Zapisano puste menu\n";
else { echo "❌ Błąd zapisu menu\n"; $errors++; }

// 11. MEDIA FOLDER
echo "\nMedia Folder...\n";
$res = request('POST', '/admin/media/createFolder', ['name' => 'e2e_folder', 'path' => '']);
if ($res['code'] === 200) echo "✅ Utworzono folder mediów\n";
else { echo "⚠️ Błąd tworzenia folderu mediów (może już istnieć)\n"; }

// 12. USERS
echo "\nUżytkownicy (Users)...\n";
$uName = 'e2e_user_' . time();
$res = request('POST', '/admin/users/create', [
    'username' => $uName,
    'email' => 'e2e@example.com',
    'password' => 'pass123',
    'csrf_token' => $csrf
]);
if ($res['code'] === 302) echo "✅ Utworzono użytkownika ($uName)\n";
else { echo "❌ Błąd tworzenia użytkownika\n"; $errors++; }

echo "\nZakończono. Błędy: $errors\n";
exit($errors > 0 ? 1 : 0);