<?php
$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$errors = 0;

// Definicja tras i oczekiwanych statusów: [Metoda, Ścieżka, Gość, User, Admin]
$routes = [
    // Publiczne
    ['GET',  '/',                       200, 200, 200],
    ['GET',  '/login',                  200, 200, 200],
    ['POST', '/submit-form',            302, 302, 302], // Przekierowanie po wysłaniu
    
    // Tylko Zalogowani (User + Admin)
    ['GET',  '/change-password',        302, 200, 200],
    
    // Tylko Admin
    ['GET',  '/admin',                  302, 302, 200],
    ['GET',  '/admin/users',            302, 302, 200],
    ['GET',  '/admin/settings',         302, 302, 200],
    ['POST', '/admin/pages/create',     302, 302, 302], // 302 bo redirect po zapisie
    
    // Błędy
    ['GET',  '/non-existent-page-404',  404, 404, 404],
];

function request($method, $path, $postData = null) {
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Ważne: nie idziemy za 302, by sprawdzić middleware

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function runTests($roleName, $statusIndex) {
    global $routes, $errors;
    echo "\n--- Testowanie jako: $roleName ---\n";
    foreach ($routes as $route) {
        $method = $route[0];
        $path   = $route[1];
        $expected = $route[$statusIndex];
        
        $actual = request($method, $path);
        
        if ($actual === $expected) {
            echo "✅ [$method] $path -> $actual\n";
        } else {
            echo "❌ [$method] $path -> Oczekiwano $expected, otrzymano $actual\n";
            $errors++;
        }
    }
}

// --- 1. TESTY JAKO GOŚĆ ---
runTests('GUEST', 2);

// --- Przygotowanie konta testowego (User) przez bazę ---
// (Zakładamy, że test-db lub install utworzyły już admina)

// --- 2. TESTY JAKO UŻYTKOWNIK ---
// Tutaj należałoby najpierw stworzyć usera i się zalogować
// Na potrzeby testu używamy uproszczonego logowania (jeśli system ma rejestrację):
request('POST', '/login', ['uname' => 'user', 'pass' => 'user123']); 
runTests('USER', 3);

// --- 3. TESTY JAKO ADMIN ---
if (file_exists($cookieFile)) unlink($cookieFile); // Czyścimy sesję usera
request('POST', '/login', ['uname' => 'admin', 'pass' => 'admin']); 
runTests('ADMIN', 4);

echo "\nZakończono. Łącznie błędów: $errors\n";
exit($errors > 0 ? 1 : 0);