<?php
// cron.php - Uruchamiaj za pomocą zadania CRON na serwerze
require_once __DIR__ . '/../vendor/autoload.php';

// Zbuduj ścieżki i załaduj .env tak jak w public/index.php
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) $_ENV[$key] = $value;
}

spl_autoload_register(function ($class) {
    $prefix = 'CMS\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    require $base_dir . str_replace('\\', '/', substr($class, $len)) . '.php';
});

use CMS\Core\Database;
use CMS\Services\MailerService;

$db = Database::getInstance();
$mailer = new MailerService();

// Pobierz maile do wysłania, dla których minął czas harmonogramu
$queue = $db->query("SELECT * FROM pa_email_queue WHERE status = 'pending' AND scheduled_for <= NOW()")->fetchAll();

foreach ($queue as $task) {
    // 1. Oznacz jako w trakcie przetwarzania
    $db->query("UPDATE pa_email_queue SET status = 'processing' WHERE id = ?", [$task['id']]);
    
    // 2. Pobierz Szablon
    $template = $db->query("SELECT * FROM pa_email_templates WHERE id = ?", [$task['template_id']])->fetch();
    if (!$template) continue;

    $recipients = [];

    // 3. Dodaj odbiorców z listy systemowej użytkowników (Domyślna lista)
    if ($task['list_id'] == 1) { // 1 = Domyślna wpisana przez nas w SQL
        $users = $db->query("SELECT uname as name, email FROM pa_users WHERE admin = 0")->fetchAll();
        foreach ($users as $u) $recipients[] = $u;
    } 
    // Dodaj z innej listy
    else if ($task['list_id'] > 1) {
        $subs = $db->query("SELECT name, email FROM pa_mailing_subscribers WHERE list_id = ?", [$task['list_id']])->fetchAll();
        foreach ($subs as $s) $recipients[] = $s;
    }

    // 4. Dodaj ręcznych odbiorców z inputa (rozdzieleni przecinkiem)
    if (!empty($task['custom_emails'])) {
        $emails = explode(',', $task['custom_emails']);
        foreach ($emails as $em) {
            $recipients[] = ['name' => 'Użytkowniku', 'email' => trim($em)];
        }
    }

    // 5. Pętla wysyłki z dynamicznym wstrzykiwaniem danych!
    foreach ($recipients as $recipient) {
        $body = str_replace(
            ['{{uname}}', '{{email}}'], 
            [$recipient['name'], $recipient['email']], 
            $template['body']
        );
        $subject = str_replace('{{uname}}', $recipient['name'], $template['subject']);

        $sendResult = $mailer->send($recipient['email'], $subject, $body);

        // 6. Logowanie historii i błędów (widok podejrzenia dostarczenia)
        if ($sendResult === true) {
            $db->query("INSERT INTO pa_email_logs (queue_id, user_email, status) VALUES (?, ?, 'sent')", [$task['id'], $recipient['email']]);
        } else {
            $db->query("INSERT INTO pa_email_logs (queue_id, user_email, status, error_message) VALUES (?, ?, 'failed', ?)", [$task['id'], $recipient['email'], $sendResult]);
        }
    }

    // 7. Zakończ zadanie
    $db->query("UPDATE pa_email_queue SET status = 'completed' WHERE id = ?", [$task['id']]);
}

echo "CRON zakończył zadanie.\n";