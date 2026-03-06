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

        // Inicjalizacja PHPMailera (Pobierany automatycznie przez Composera)
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = $config['smtp_host'] ?? '';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $config['smtp_user'] ?? '';
        $this->mail->Password = $config['smtp_pass'] ?? '';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $config['smtp_port'] ?? 587;
        
        $domain = $_SERVER['HTTP_HOST'] ?? 'CMS';
        $this->mail->setFrom($config['smtp_user'] ?? 'no-reply@' . $domain, $domain);
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);
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