<?php
namespace CMS\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use CMS\Core\Database;

class MailerService {
    private $mail;

    public function __construct() {
        $db = Database::getInstance();
        $settings = $db->query("SELECT setting_key, setting_value FROM pa_settings WHERE setting_key LIKE 'smtp_%'")->fetchAll();
        $config = [];
        foreach($settings as $s) $config[$s['setting_key']] = $s['setting_value'];

        // Inicjalizacja PHPMailera
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = $config['smtp_host'] ?? '';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $config['smtp_user'] ?? '';
        $this->mail->Password = $config['smtp_pass'] ?? '';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $config['smtp_port'] ?? 587;
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);

        // --- ZABEZPIECZONA LOGIKA NADAWCY ---
        $domain = $_SERVER['HTTP_HOST'] ?? 'domena.pl';
        $domain = preg_replace('/:\d+$/', '', $domain); // Usuwa port (np. z localhost:8000 zostawia localhost)
        
        // Zabezpieczenie przed błędem walidacji (localhost nie jest poprawną pełną domeną dla PHPMailera)
        $safeDomain = ($domain === 'localhost' || $domain === '127.0.0.1') ? 'localhost.local' : $domain;
        $fromEmail = !empty($config['smtp_user']) ? $config['smtp_user'] : 'no-reply@' . $safeDomain;

        try {
            $this->mail->setFrom($fromEmail, $domain);
        } catch (Exception $e) {
            // Zapobiega Crashowi (HTTP 500) jeśli adres z jakiegoś powodu nie przejdzie rygorystycznej walidacji
        }
    }

    public function send($to, $subject, $body) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return $this->mail->ErrorInfo;
        }
    }
}