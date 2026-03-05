<?php
// Skrypt E2E (End-to-End) do testowania zabezpieczeń i routingów.
// Zwraca kod 0 (sukces) lub 1 (błąd dla GitHub Actions).

$baseUrl = 'http://localhost:8000';
$errors = 0;

function testEndpoint($method, $path, $expectedStatus, $postData = null) {
    global $baseUrl, $errors;
    $url = $baseUrl . $path;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Tylko nagłówki
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === $expectedStatus) {
        echo "✅ PASSED: [$method] $path (Got $httpCode)\n";
    } else {
        echo "❌ FAILED: [$method] $path (Expected $expectedStatus, Got $httpCode)\n";
        $errors++;
    }
}

echo "Rozpoczynam testy E2E (Security & Routing)...\n\n";

// 1. Testy dostępu publicznego
testEndpoint('GET', '/', 200); // Strona główna powinna działać
testEndpoint('GET', '/login', 200); // Ekran logowania powinien działać
testEndpoint('GET', '/nie-ma-takiej-strony', 404); // Błąd 404 działa

// 2. Testy zabezpieczeń (Middleware) - Brak sesji
testEndpoint('GET', '/admin', 302); // AdminMiddleware powinno przekierować (302) do logowania
testEndpoint('GET', '/change-password', 302); // AuthMiddleware powinno przekierować (302)

// 3. Testy zabezpieczeń CSRF
testEndpoint('POST', '/admin/users/create', 302, ['username' => 'haker']);

// 4. Testy API formularzy
testEndpoint('POST', '/submit-form', 302); // Przekierowanie po złym/dobrym formularzu

echo "\nZakończono testy. Błędów: $errors\n";
exit($errors > 0 ? 1 : 0);