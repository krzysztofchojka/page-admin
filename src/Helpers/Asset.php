<?php
namespace CMS\Helpers;

class Asset {
    public static function url($path) {
        // Ustalenie ścieżki fizycznej pliku na serwerze
        $filePath = __DIR__ . '/../../public' . $path;
        
        $version = '1.0';

        // Jeśli plik istnieje, sprawdzamy strategię wersjonowania
        if (file_exists($filePath)) {
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                // W trybie DEV: Zawsze aktualny czas (brak cache)
                $version = time();
            } else {
                // W trybie PROD: Data ostatniej modyfikacji pliku (inteligentny cache)
                $version = filemtime($filePath);
            }
        }

        return $path . '?v=' . $version;
    }
}