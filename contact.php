<?php
// Formularz kontaktowy NovoPix: wysyla wiadomosc na contact@novopix.uk (wlasny skrypt, bez uslug zewnetrznych).
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// Jezyk formularza: lang=en|pl (domyslnie en). Odpowiedzi JSON maja komunikat w tym jezyku.
$lang = (strtolower(trim((string)($_POST['lang'] ?? 'en'))) === 'pl') ? 'pl' : 'en';
$MSG = [
    'en' => ['ok' => 'Thank you. We will reply by e-mail.', 'invalid' => 'Please check your name, e-mail address and message.', 'limit' => 'Too many messages. Please try again later or write to contact@novopix.uk.', 'fail' => 'Could not send. Please write to contact@novopix.uk.', 'method' => 'Method not allowed.'],
    'pl' => ['ok' => 'Dziękujemy. Odpowiemy e-mailem.', 'invalid' => 'Sprawdź imię, adres e-mail i treść wiadomości.', 'limit' => 'Zbyt wiele wiadomości. Spróbuj później lub napisz na contact@novopix.uk.', 'fail' => 'Nie udało się wysłać. Napisz na contact@novopix.uk.', 'method' => 'Niedozwolona metoda.'],
];
function out($code, $ok, $key = null) { global $MSG, $lang; http_response_code($code); $r = ['ok' => $ok]; $k = $key ?? ($ok ? 'ok' : 'fail'); $r['message'] = $MSG[$lang][$k]; echo json_encode($r, JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false, 'method');
if (!empty($_POST['website'])) out(200, true); // pulapka na boty
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$msg = trim((string)($_POST['message'] ?? ''));
if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || mb_strlen($msg) < 2 || mb_strlen($msg) > 4000 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) out(422, false, 'invalid');
// limit: 5 wiadomosci na godzine z jednego IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$f = sys_get_temp_dir() . '/np_contact_' . md5($ip);
$now = time(); $hits = [];
if (is_file($f)) { $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($f))), fn($t) => $now - $t < 3600); }
if (count($hits) >= 5) out(429, false, 'limit');
$hits[] = $now; @file_put_contents($f, implode(',', $hits));
$clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
$subject = 'NovoPix: ' . ($lang === 'pl' ? 'wiadomość od ' : 'message from ') . $clean($name) . ' [' . strtoupper($lang) . ']';
$body = $msg . "\n\n--\n" . $name . " <" . $email . ">\nLanguage: " . $lang . "\nIP: " . $ip . "\n";
$headers = "From: NovoPix <contact@novopix.uk>\r\nReply-To: " . $clean($email) . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$sent = @mail('contact@novopix.uk', '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-fcontact@novopix.uk');
if (!$sent) out(500, false, 'fail');
out(200, true);
