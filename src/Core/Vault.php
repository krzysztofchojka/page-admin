<?php
namespace CMS\Core;

class Vault {
    private $cipher = "aes-256-cbc";
    private $key;

    public function __construct() {
        // Load key from .env. If not set, use a fallback (BUT YOU SHOULD SET IT)
        $envKey = parse_ini_file(__DIR__ . '/../../.env')['APP_KEY'] ?? 'base64:UnsafeDefaultKey==';
        if (strpos($envKey, 'base64:') === 0) {
            $this->key = base64_decode(substr($envKey, 7));
        } else {
            $this->key = $envKey;
        }
    }

    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        return base64_encode($iv . $encrypted); // Return IV + Data as one string
    }

    public function decrypt($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
    }
}