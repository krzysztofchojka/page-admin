<?php
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);
$errors = 0;

// Definicja tras: [Metoda, Ścieżka, Gość, User, Admin]
$routes = [
    ['GET',  '/',                       200, 200, 200],
    ['GET',  '/login',                  200, 200, 200],
    ['POST', '/submit-form',            302, 302, 302],
    
    // Zmieniamy oczekiwania dla change-password: 
    // Zalogowany user (User/Admin) z AKTYWNYM hasłem zostanie przekierowany stąd (302) do login, 
    // bo ta strona jest tylko dla osób w procesie zmiany hasła (temp_user_id).
    ['GET',  '/change-password',        302, 302, 302], 
    
    ['GET',  '/admin',                  302, 302, 200],
    ['GET',  '/admin/users',            302, 302, 200],
    ['GET',  '/admin/settings',         302, 302, 200],
    ['GET',  '/non-existent-404',       404, 404, 404],
];

function request($method, $path, $postData = null) {
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $response];
}

// Funkcja pomocnicza do logowania z obsługą CSRF
function login($username, $password) {
    // 1. Pobierz stronę logowania, aby dostać ciasteczko sesji i token CSRF
    $res = request('GET', '/login');
    preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
    $token = $matches[1] ?? '';

    // 2. Wyślij POST z loginem i tokenem
    return request('POST', '/login', [
        'login' => $username,
        'password' => $password,
        'csrf_token' => $token
    ]);
}

function runTests($roleName, $statusIndex) {
    global $routes, $errors;
    echo "\n--- Testowanie jako: $roleName ---\n";
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

echo "\nZakończono. Błędy: $errors\n";
exit($errors > 0 ? 1 : 0);