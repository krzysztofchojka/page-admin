<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class EmailController {
    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn()) { header('Location: /login'); exit; }
    }

    public function index() {
        ob_start();
        require_once __DIR__ . '/../Views/admin/email/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    // --- BŁYSKAWICZNA WYSYŁKA Z UI (Odpowiedz / Przekaż) ---
    public function sendDirect() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['to']) || empty($data['subject']) || empty($data['body'])) {
            echo json_encode(['status' => 'error', 'message' => 'Wypełnij poprawnie wszystkie pola (Odbiorca, Temat, Treść).']);
            exit;
        }

        require_once __DIR__ . '/../Services/MailerService.php';
        $mailer = new \CMS\Services\MailerService();

        $result = $mailer->send($data['to'], $data['subject'], $data['body']);

        if ($result === true) {
            // ZAPIS DO LOKALNEJ BAZY DANYCH (HISTORIA)
            $db = \CMS\Core\Database::getInstance();
            $db->query("CREATE TABLE IF NOT EXISTS pa_sent_emails (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body LONGTEXT NOT NULL,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $db->query("INSERT INTO pa_sent_emails (recipient, subject, body, sent_at) VALUES (?, ?, ?, NOW())", [
                $data['to'], 
                $data['subject'], 
                $data['body']
            ]);

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => is_string($result) ? $result : 'Błąd komunikacji z serwerem SMTP.']);
        }
        exit;
    }

    // --- POBIERANIE HISTORII WYSŁANYCH WIADOMOŚCI ---
    public function fetchSentEmails() {
        header('Content-Type: application/json');
        $db = \CMS\Core\Database::getInstance();
        try {
            $emails = $db->query("SELECT id, recipient as `from`, subject, body, sent_at as `date` FROM pa_sent_emails ORDER BY id DESC LIMIT 50")->fetchAll();
            echo json_encode(['status' => 'success', 'emails' => $emails]);
        } catch (\Exception $e) {
            // Jeśli tabela jeszcze nie istnieje (bo nic nie wysłano), zwracamy pustą tablicę
            echo json_encode(['status' => 'success', 'emails' => []]);
        }
        exit;
    }

    public function deleteTemplate() {
        \CMS\Core\Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $id = $_GET['id'] ?? 0;
        $db = \CMS\Core\Database::getInstance();

        // 1. Sprawdzamy, czy jakaś wiadomość nie czeka w kolejce do wysłania z tym szablonem
        $inQueue = $db->query("SELECT COUNT(*) as c FROM pa_email_queue WHERE template_id = ?", [$id])->fetch()['c'];
        if ($inQueue > 0) {
            \CMS\Core\Session::setFlash("Nie można usunąć: Szablon jest używany przez oczekujące zadania w kolejce wysyłkowej.", "error");
            header('Location: /admin/email/templates');
            exit;
        }

        // 2. Sprawdzamy czy szablon nie jest podpięty w ustawieniach formularzy jako autoresponder
        $inFormsAdmin = $db->query("SELECT COUNT(*) as c FROM pa_forms WHERE settings LIKE ?", ['%"adminTemplate":"'.$id.'"%'])->fetch()['c'];
        $inFormsUser = $db->query("SELECT COUNT(*) as c FROM pa_forms WHERE settings LIKE ?", ['%"userTemplate":"'.$id.'"%'])->fetch()['c'];

        if ($inFormsAdmin > 0 || $inFormsUser > 0) {
            \CMS\Core\Session::setFlash("Nie można usunąć: Szablon jest podpięty pod powiadomienia w formularzu.", "error");
            header('Location: /admin/email/templates');
            exit;
        }

        $db->query("DELETE FROM pa_email_templates WHERE id = ?", [$id]);
        \CMS\Core\Session::setFlash("Szablon został usunięty.", "success");
        header('Location: /admin/email/templates');
        exit;
    }

    // --- 1B. ASYNCHRONICZNE POBIERANIE MAILI (AJAX + CACHE 5 MINUT) ---
    public function fetchEmails() {
        header('Content-Type: application/json');
        
        // Zapisujemy cache w systemowym folderze tymczasowym serwera (bezpieczne i zawsze zapisywalne)
        $cacheFile = sys_get_temp_dir() . '/cms_imap_cache.json';
        $forceRefresh = isset($_GET['force']) && $_GET['force'] == '1';
        
        // Sprawdzanie Cache (300 sekund = 5 minut)
        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            if ($cacheData) {
                echo json_encode(array_merge(['status' => 'success', 'source' => 'cache'], $cacheData));
                exit;
            }
        }

        $db = \CMS\Core\Database::getInstance();
        $settingsRaw = $db->query("SELECT * FROM pa_settings WHERE setting_key LIKE 'imap_%'")->fetchAll();
        $imapSet = []; foreach($settingsRaw as $s) $imapSet[$s['setting_key']] = $s['setting_value'];
        
        $emails = [];
        $imapError = null;
        $debugLog = [];

        if (!empty($imapSet['imap_host']) && !empty($imapSet['imap_user'])) {
            require_once __DIR__ . '/../Libs/MiniImap.php';
            $imap = new \CMS\Libs\MiniImap();
            
            if ($imap->connect($imapSet['imap_host'], $imapSet['imap_port'], $imapSet['imap_user'], $imapSet['imap_pass'])) {
                $emails = $imap->getRecentEmails(10);
                $imap->close();
            } else {
                $imapError = "Błąd połączenia z serwerem poczty. Sprawdź host, port i hasło.";
            }
            $debugLog = $imap->debugLog;
        } else {
            $imapError = "Brak skonfigurowanego serwera IMAP w Ustawieniach.";
        }

        // Zwróć błąd jeśli wystąpił
        if ($imapError) {
            echo json_encode(['status' => 'error', 'message' => $imapError, 'log' => $debugLog]);
            exit;
        }

        // Zapisz do Cache
        $cacheContent = json_encode(['emails' => $emails, 'log' => $debugLog]);
        file_put_contents($cacheFile, $cacheContent);

        // Zwróć świeże dane do przeglądarki
        echo json_encode(['status' => 'success', 'source' => 'server', 'emails' => $emails, 'log' => $debugLog]);
        exit;
    }

    // --- 1C. ODCZYT POJEDYNCZEGO MAILA (AJAX) ---
    public function readEmail() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? 0;
        if (!$id) { echo json_encode(['status' => 'error', 'message' => 'Brak ID wiadomości.']); exit; }

        $db = \CMS\Core\Database::getInstance();
        $settingsRaw = $db->query("SELECT * FROM pa_settings WHERE setting_key LIKE 'imap_%'")->fetchAll();
        $imapSet = []; foreach($settingsRaw as $s) $imapSet[$s['setting_key']] = $s['setting_value'];

        require_once __DIR__ . '/../Libs/MiniImap.php';
        $imap = new \CMS\Libs\MiniImap();
        
        if ($imap->connect($imapSet['imap_host'], $imapSet['imap_port'], $imapSet['imap_user'], $imapSet['imap_pass'])) {
            $body = $imap->getEmailBody($id);
            $imap->close();
            echo json_encode(['status' => 'success', 'body' => $body]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Błąd połączenia z serwerem.']);
        }
        exit;
    }

    // --- 2. SZABLONY ---
    public function templates() {
        $db = Database::getInstance();
        $templates = $db->query("SELECT * FROM pa_email_templates ORDER BY id DESC")->fetchAll();
        ob_start(); require_once __DIR__ . '/../Views/admin/email/templates.php';
        $content = ob_get_clean(); require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function createTemplate() {
        ob_start(); require_once __DIR__ . '/../Views/admin/email/template_builder.php';
        $content = ob_get_clean(); require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function editTemplate() {
        $id = $_GET['id'] ?? 0;
        $db = Database::getInstance();
        $template = $db->query("SELECT * FROM pa_email_templates WHERE id = ?", [$id])->fetch();
        if (!$template) { header('Location: /admin/email/templates'); exit; }
        ob_start(); require_once __DIR__ . '/../Views/admin/email/template_builder.php';
        $content = ob_get_clean(); require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function saveTemplate() {
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? ''); // <-- DODANE
        
        $db = Database::getInstance();
        if (!empty($_POST['id'])) {
            // Aktualizacja
            $db->query("UPDATE pa_email_templates SET title = ?, subject = ?, body = ? WHERE id = ?", [
                $_POST['title'], $_POST['subject'], $_POST['body'], $_POST['id']
            ]);
        } else {
            // Nowy
            $db->query("INSERT INTO pa_email_templates (title, subject, body) VALUES (?, ?, ?)", [
                $_POST['title'], $_POST['subject'], $_POST['body']
            ]);
        }
        Session::setFlash("Szablon zapisany!", "success");
        header('Location: /admin/email/templates'); exit;
    }

    // --- 3. LISTY MAILINGOWE ---
    public function lists() {
        $db = Database::getInstance();
        $lists = $db->query("SELECT * FROM pa_mailing_lists ORDER BY id DESC")->fetchAll();
        ob_start(); require_once __DIR__ . '/../Views/admin/email/lists.php';
        $content = ob_get_clean(); require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function createList() {
        \CMS\Core\Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $name = trim($_POST['name'] ?? 'Nowa lista');
        Database::getInstance()->query("INSERT INTO pa_mailing_lists (name, is_default) VALUES (?, 0)", [$name]);
        Session::setFlash("Lista utworzona!", "success");
        header('Location: /admin/email/lists'); exit;
    }

    public function manageList() {
        $id = $_GET['id'] ?? 0;
        $db = Database::getInstance();
        $list = $db->query("SELECT * FROM pa_mailing_lists WHERE id = ?", [$id])->fetch();
        if (!$list) { header('Location: /admin/email/lists'); exit; }
        $subscribers = $db->query("SELECT * FROM pa_mailing_subscribers WHERE list_id = ? ORDER BY id DESC", [$id])->fetchAll();
        
        ob_start(); require_once __DIR__ . '/../Views/admin/email/list_manage.php';
        $content = ob_get_clean(); require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function addSubscriber() {
        \CMS\Core\Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $listId = $_POST['list_id'];
        $email = trim($_POST['email']);
        $name = trim($_POST['name'] ?? '');
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Database::getInstance()->query("INSERT INTO pa_mailing_subscribers (list_id, email, name) VALUES (?, ?, ?)", [$listId, $email, $name]);
                Session::setFlash("Dodano subskrybenta.", "success");
            } catch (\Exception $e) { Session::setFlash("Ten email już jest na liście.", "error"); }
        }
        header('Location: /admin/email/lists/manage?id=' . $listId); exit;
    }

    public function removeSubscriber() {
        $id = $_GET['id'] ?? 0;
        $listId = $_GET['list_id'] ?? 0;
        Database::getInstance()->query("DELETE FROM pa_mailing_subscribers WHERE id = ?", [$id]);
        Session::setFlash("Usunięto.", "success");
        header('Location: /admin/email/lists/manage?id=' . $listId); exit;
    }

    // --- KOLEJKA WYSYŁEK ---
    public function queue() {
        $db = \CMS\Core\Database::getInstance();
        // Pobieramy kolejkę i dołączamy statystyki z logów (ile wysłano, ile błędów)
        $queue = $db->query("
            SELECT q.*, t.title as template_title, l.name as list_name,
                   (SELECT COUNT(*) FROM pa_email_logs WHERE queue_id = q.id AND status = 'sent') as sent_count,
                   (SELECT COUNT(*) FROM pa_email_logs WHERE queue_id = q.id AND status = 'failed') as failed_count
            FROM pa_email_queue q 
            LEFT JOIN pa_email_templates t ON q.template_id = t.id 
            LEFT JOIN pa_mailing_lists l ON q.list_id = l.id 
            ORDER BY q.id DESC
        ")->fetchAll();
        
        ob_start();
        require_once __DIR__ . '/../Views/admin/email/queue.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    // 3. PLANOWANIE WYSYŁKI DO CRON (Schedule)
    public function schedule() {
        $db = \CMS\Core\Database::getInstance();
        $templateId = $_POST['template_id'] ?? 0;
        $listId = !empty($_POST['list_id']) ? $_POST['list_id'] : null;
        $customEmails = $_POST['custom_emails'] ?? '';
        
        // Zapisujemy jako oczekujące (bez daty wymusza ręczne kliknięcie, ew. natychmiastowy CRON)
        $db->query("INSERT INTO pa_email_queue (template_id, list_id, custom_emails, status) VALUES (?, ?, ?, 'pending')", [
            $templateId, $listId, $customEmails
        ]);

        \CMS\Core\Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        \CMS\Core\Session::setFlash("Zadanie dodane do kolejki.", "success");
        header('Location: /admin/email/queue');
        exit;
    }

    public function triggerJob() {
        \CMS\Core\Session::init(); // Zawsze na początku by uniknąć błędu 500 (headers already sent)
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $id = $_GET['id'] ?? 0;
        if (!$id) { header('Location: /admin/email/queue'); exit; }

        $db = \CMS\Core\Database::getInstance();
        
        // --- OBSŁUGA "WYŚLIJ PONOWNIE" ---
        $isResend = isset($_GET['resend']) && $_GET['resend'] == 1;
        if ($isResend) {
            $oldTask = $db->query("SELECT * FROM pa_email_queue WHERE id = ?", [$id])->fetch();
            if ($oldTask) {
                // Klonujemy zadanie jako nowe (oczekujące)
                $db->query("INSERT INTO pa_email_queue (template_id, list_id, custom_emails, status) VALUES (?, ?, ?, 'pending')", [
                    $oldTask['template_id'], $oldTask['list_id'], $oldTask['custom_emails']
                ]);
                \CMS\Core\Session::setFlash("Zadanie zostało skopiowane do ponownej wysyłki.", "success");
            }
            header('Location: /admin/email/queue');
            exit;
        }

        // --- STANDARDOWA WYSYŁKA ---
        $task = $db->query("SELECT * FROM pa_email_queue WHERE id = ? AND status = 'pending'", [$id])->fetch();
        if ($task) {
            // Zabezpieczenie przed podwójnym kliknięciem
            $db->query("UPDATE pa_email_queue SET status = 'processing' WHERE id = ?", [$id]);
            $template = $db->query("SELECT * FROM pa_email_templates WHERE id = ?", [$task['template_id']])->fetch();
            
            if (!$template) {
                $db->query("UPDATE pa_email_queue SET status = 'failed' WHERE id = ?", [$id]);
                \CMS\Core\Session::setFlash("Błąd: Szablon został usunięty.", "error");
                header('Location: /admin/email/queue');
                exit;
            }

            require_once __DIR__ . '/../Services/MailerService.php';
            $mailer = new \CMS\Services\MailerService();
            $recipients = [];

            // 1. Z Listy Systemowej (tylko poprawne maile)
            if ($task['list_id'] == 1) {
                $users = $db->query("SELECT uname as name, email FROM pa_users WHERE admin = 0 AND email IS NOT NULL AND email != ''")->fetchAll();
                foreach ($users as $u) $recipients[] = $u;
            } 
            // 2. Z innej listy
            else if ($task['list_id'] > 1) {
                $subs = $db->query("SELECT name, email FROM pa_mailing_subscribers WHERE list_id = ?", [$task['list_id']])->fetchAll();
                foreach ($subs as $s) $recipients[] = $s;
            }

            // 3. Z customowych maili (zabezpieczenie przed błędami składniowymi)
            if (!empty($task['custom_emails'])) {
                $emails = array_map('trim', explode(',', $task['custom_emails']));
                foreach ($emails as $em) {
                    if (filter_var($em, FILTER_VALIDATE_EMAIL)) { // Zapobiega 500 error w PHPMailerze
                        $recipients[] = ['name' => 'Użytkowniku', 'email' => $em];
                    }
                }
            }

            // Usunięcie duplikatów maili, aby nie wysyłać podwójnie
            $uniqueRecipients = [];
            foreach ($recipients as $r) $uniqueRecipients[$r['email']] = $r;

            // Pętla wysyłająca
            foreach ($uniqueRecipients as $recipient) {
                $body = str_replace(['{{uname}}', '{{email}}'], [$recipient['name'], $recipient['email']], $template['body']);
                $subject = str_replace('{{uname}}', $recipient['name'], $template['subject']);
                
                $sendResult = $mailer->send($recipient['email'], $subject, $body);

                if ($sendResult === true) {
                    $db->query("INSERT INTO pa_email_logs (queue_id, user_email, status) VALUES (?, ?, 'sent')", [$task['id'], $recipient['email']]);
                } else {
                    $errMsg = is_string($sendResult) ? $sendResult : 'Nieznany błąd';
                    $db->query("INSERT INTO pa_email_logs (queue_id, user_email, status, error_message) VALUES (?, ?, 'failed', ?)", [$task['id'], $recipient['email'], $errMsg]);
                }
            }
            // Zmiana statusu na ukończony
            $db->query("UPDATE pa_email_queue SET status = 'completed' WHERE id = ?", [$id]);
        }
        
        \CMS\Core\Session::setFlash("Proces wysyłki zakończony!", "success");
        header('Location: /admin/email/queue');
        exit;
    }
}