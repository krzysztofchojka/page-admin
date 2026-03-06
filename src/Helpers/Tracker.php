<?php
namespace CMS\Helpers;

use CMS\Core\Database;

class Tracker {
    public static function logVisit($url) {
        // Ignorujemy zapytania do panelu admina oraz pliki statyczne
        if (strpos($url, '/admin') === 0 || strpos($url, '/assets') === 0) return;
        
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        // Proste filtrowanie najpopularniejszych botów
        if (preg_match('/bot|crawl|slurp|spider|lighthouse/i', $ua)) return;

        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $date = date('Y-m-d');
        
        // Generowanie anonimowego, bezciasteczkowego identyfikatora unikalnego na dany dzień
        $appKey = $_ENV['APP_KEY'] ?? 'default_fallback_key';
        $hash = hash('sha256', $ip . $ua . $date . $appKey);

        $db = Database::getInstance();
        
        // Sprawdzamy czy ta wizyta na tej stronie już dziś wystąpiła (zapobiega nabijaniu statystyk przez F5)
        $stmt = $db->query("SELECT id FROM pa_statistics WHERE visit_date = ? AND visitor_hash = ? AND page_url = ?", [$date, $hash, $url]);
        
        if (!$stmt->fetch()) {
            // Ustalanie kraju (Szybkie sprawdzenie z nagłówków Cloudflare)
            $country = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? null;
            
            // Fallback na darmowe API z limitem czasowym (1 sekunda, żeby nie blokować ładowania strony)
            if (!$country && $ip !== '127.0.0.1' && $ip !== '::1') {
                $ctx = stream_context_create(['http' => ['timeout' => 1]]);
                $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
                if ($json) {
                    $data = json_decode($json);
                    $country = $data->countryCode ?? 'XX';
                }
            }
            $country = $country ?: 'XX';

            // Zapis do bazy
            $db->query("INSERT IGNORE INTO pa_statistics (visit_date, visitor_hash, page_url, country_code) VALUES (?, ?, ?, ?)", [$date, $hash, $url, $country]);
        }
    }
}