<?php
namespace CMS\Libs;

class MiniImap {
    private $stream;
    private $tagCount = 1;
    public $debugLog = []; // Tablica na logi

    private function log($msg) {
        $this->debugLog[] = date('H:i:s') . " " . $msg;
    }

    public function connect($host, $port, $user, $pass) {
        $this->log("Łączenie z ssl://$host:$port...");
        
        // Wyłączamy restrykcyjne sprawdzanie SSL (częsty problem na hostingach)
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $this->stream = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        
        if (!$this->stream) {
            $this->log("Błąd połączenia sieciowego: $errno - $errstr");
            return false;
        }
        
        $this->log("Połączono. Czekam na powitanie...");
        $greeting = fgets($this->stream);
        $this->log("SERWER: " . trim($greeting));
        
        $this->log("Próba logowania...");
        $res = $this->send("LOGIN \"$user\" \"$pass\"");
        return stripos($res, 'OK') !== false;
    }

    private function send($cmd) {
        $tag = "A" . str_pad($this->tagCount++, 3, '0', STR_PAD_LEFT);
        
        // Ukrywamy hasło w logach!
        $logCmd = strpos($cmd, 'LOGIN') === 0 ? "LOGIN \"***\" \"***\"" : $cmd;
        $this->log("KLIENT: $tag $logCmd");
        
        fwrite($this->stream, "$tag $cmd\r\n");
        $response = '';
        
        while ($line = fgets($this->stream)) {
            $this->log("SERWER: " . trim($line));
            $response .= $line;
            if (stripos($line, "$tag OK") === 0 || stripos($line, "$tag NO") === 0 || stripos($line, "$tag BAD") === 0) {
                break;
            }
        }
        return $response;
    }

    public function getRecentEmails($limit = 10) {
        $res = $this->send("SELECT INBOX");
        preg_match('/\* (\d+) EXISTS/i', $res, $matches);
        $total = isset($matches[1]) ? (int)$matches[1] : 0;
        $this->log("Zidentyfikowano $total wiadomości w INBOX.");

        $emails = [];
        if ($total > 0) {
            $start = max(1, $total - $limit + 1);
            $fetchRes = $this->send("FETCH $start:$total (BODY.PEEK[HEADER.FIELDS (DATE FROM SUBJECT)])");

            $currentEmail = null;
            $lines = explode("\r\n", $fetchRes);
            
            foreach ($lines as $line) {
                // TUTAJ ZMIANA: Zapisujemy ID wiadomości do zmiennej $m[1]
                if (preg_match('/\* (\d+) FETCH/i', $line, $m)) {
                    if ($currentEmail) $emails[] = (object)$currentEmail;
                    $currentEmail = ['id' => $m[1], 'subject' => '(Brak tematu)', 'from' => 'Nieznany', 'date' => date('Y-m-d H:i')];
                } elseif ($currentEmail !== null) {
                    if (stripos($line, 'Subject:') === 0) {
                        $currentEmail['subject'] = $this->decode(substr($line, 8));
                    } elseif (stripos($line, 'From:') === 0) {
                        $currentEmail['from'] = $this->decode(substr($line, 5));
                    } elseif (stripos($line, 'Date:') === 0) {
                        $currentEmail['date'] = trim(substr($line, 5));
                    }
                }
            }
            if ($currentEmail) $emails[] = (object)$currentEmail;
        }
        return array_reverse($emails);
    }

    public function getEmailBody($id) {
        // 1. DODANE: Zawsze upewniamy się, że jesteśmy w skrzynce odbiorczej
        $this->log("Wybieranie skrzynki INBOX...");
        $this->send("SELECT INBOX");

        $this->log("Pobieranie pełnej treści dla ID: $id");
        $res = $this->send("FETCH $id (BODY.PEEK[])");
        
        // 2. Agresywne czyszczenie tagów serwera IMAP (reszta bez zmian)
        $rawEmail = preg_replace('/^\* \d+ FETCH.*?\r?\n/i', '', $res);
        $rawEmail = preg_replace('/\)\r?\n[A-Z0-9]+ (OK|NO|BAD).*/is', '', $rawEmail);
        
        // 2. Oddzielenie głównych nagłówków od reszty maila
        $parts = preg_split("/\r?\n\r?\n/", $rawEmail, 2);
        $globalHeaders = $parts[0] ?? '';
        $globalBody = $parts[1] ?? $rawEmail; 
        
        $body = '';
        $encoding = '';
        $isHtml = false;
    
        // 3. Bardziej elastyczne szukanie boundary (obsługuje spacje i różne formaty)
        if (preg_match('/boundary\s*=\s*["\']?([^"\'\r\n;]+)/i', $globalHeaders, $bMatch)) {
            $boundary = trim($bMatch[1]);
            $sections = explode("--" . $boundary, $globalBody);
            
            foreach ($sections as $section) {
                if (empty(trim($section)) || strpos(trim($section), "--") === 0) continue;
                
                $subParts = preg_split("/\r?\n\r?\n/", ltrim($section), 2);
                if (count($subParts) >= 2) {
                    $pHeaders = $subParts[0];
                    $pBody = $subParts[1];
                    
                    $pEnc = '';
                    if (preg_match('/Content-Transfer-Encoding:\s*([a-zA-Z0-9\-]+)/i', $pHeaders, $eMatch)) {
                        $pEnc = strtolower($eMatch[1]);
                    }
                    
                    // Preferujemy HTML, przerywamy pętlę jeśli go znajdziemy
                    if (stripos($pHeaders, 'Content-Type: text/html') !== false) {
                        $body = $pBody;
                        $encoding = $pEnc;
                        $isHtml = true;
                        break; 
                    } elseif (stripos($pHeaders, 'Content-Type: text/plain') !== false && empty($body)) {
                        $body = $pBody;
                        $encoding = $pEnc;
                        $isHtml = false;
                    }
                }
            }
        } else {
            // Mail bez boundary (jednoczęściowy)
            $body = $globalBody;
            $isHtml = stripos($globalHeaders, 'Content-Type: text/html') !== false;
            if (preg_match('/Content-Transfer-Encoding:\s*([a-zA-Z0-9\-]+)/i', $globalHeaders, $eMatch)) {
                $encoding = strtolower($eMatch[1]);
            }
        }
    
        // 4. OSTATECZNY FALLBACK: Jeśli parsowanie zawiodło, bierzemy całą surową treść
        if (empty(trim($body))) {
            $this->log("Parsowanie MIME zawiodło. Włączam fallback surowej treści.");
            $body = $globalBody; 
            $isHtml = false; // Wymuszamy tekst, aby tagi HTML z innych miejsc nie zepsuły widoku
            
            if (!$encoding && preg_match('/Content-Transfer-Encoding:\s*([a-zA-Z0-9\-]+)/i', $globalHeaders, $eMatch)) {
                $encoding = strtolower($eMatch[1]);
            }
        }
    
        // 5. Dekodowanie
        if ($encoding == 'base64') {
            $decoded = base64_decode(trim($body));
            if ($decoded !== false) $body = $decoded;
        } elseif ($encoding == 'quoted-printable') {
            $body = quoted_printable_decode(trim($body));
        }
    
        // 6. Ostateczny bezpiecznik - jeśli nawet to jest puste, zwróć DOSŁOWNIE odpowiedź serwera
        if (empty(trim($body))) {
            $body = $res; 
            $isHtml = false;
        }
    
        // 7. Kosmetyka formatowania tekstu
        if (!$isHtml) {
            $body = nl2br(htmlspecialchars(trim($body)));
        }
    
        return $body;
    }

    private function decode($string) {
        $string = trim($string);
        return function_exists('mb_decode_mimeheader') ? mb_decode_mimeheader($string) : $string;
    }

    public function close() {
        if ($this->stream) {
            $this->send("LOGOUT");
            fclose($this->stream);
        }
    }
}