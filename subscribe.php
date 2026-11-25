<?php
// Zapis na newsletter NovoPix: wlasny skrypt, bez uslug zewnetrznych. Zapisuje adres, zgode i date do pliku POZA katalogiem strony i wysyla kopie na contact@novopix.uk.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$lang = (strtolower(trim((string)($_POST['lang'] ?? 'en'))) === 'pl') ? 'pl' : 'en';
$MSG = [
    'en' => ['ok' => 'Thank you! You are subscribed. We have sent a confirmation to your e-mail.', 'invalid' => 'Please enter a valid e-mail address and tick the consent box.', 'limit' => 'Too many attempts. Please try again later.', 'fail' => 'Could not subscribe. Please write to contact@novopix.uk.', 'method' => 'Method not allowed.'],
    'pl' => ['ok' => 'Dziękujemy! Zapisano Cię. Wysłaliśmy potwierdzenie na Twój e-mail.', 'invalid' => 'Podaj poprawny adres e-mail i zaznacz zgodę.', 'limit' => 'Zbyt wiele prób. Spróbuj później.', 'fail' => 'Nie udało się zapisać. Napisz na contact@novopix.uk.', 'method' => 'Niedozwolona metoda.'],
];
function out($code, $ok, $key = null) { global $MSG, $lang; http_response_code($code); $k = $key ?? ($ok ? 'ok' : 'fail'); echo json_encode(['ok' => $ok, 'message' => $MSG[$lang][$k]], JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false, 'method');
if (!empty($_POST['website'])) out(200, true); // pulapka na boty
$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120 || ($_POST['consent'] ?? '') !== '1') out(422, false, 'invalid');
// limit: 5 prob na godzine z jednego IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$f = sys_get_temp_dir() . '/np_sub_' . md5($ip);
$now = time(); $hits = [];
if (is_file($f)) { $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($f))), fn($t) => $now - $t < 3600); }
if (count($hits) >= 5) out(429, false, 'limit');
$hits[] = $now; @file_put_contents($f, implode(',', $hits));
// zapis do pliku poza katalogiem strony (nie do pobrania z internetu)
$dir = getenv('NOVOPIX_DATA_DIR') ?: dirname(__DIR__);
$file = rtrim($dir, '/') . '/novopix-subscribers.csv';
$known = is_file($file) && strpos((string)file_get_contents($file), '"' . $email . '"') !== false;
$saved = false;
if (!$known) { $saved = @file_put_contents($file, '"' . $email . '","' . gmdate('c') . '","' . $lang . '","consent"' . "\n", FILE_APPEND | LOCK_EX) !== false; @chmod($file, 0600); }
$clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
$h = "From: NovoPix <contact@novopix.uk>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
// kopia dla wlasciciela (zapasowa droga, gdyby zapis do pliku zawiodl)
$sentOwner = @mail('contact@novopix.uk', '=?UTF-8?B?' . base64_encode('NovoPix newsletter signup [' . strtoupper($lang) . ']') . '?=', "Signup: " . $clean($email) . "\nLanguage: " . $lang . "\nDate: " . gmdate('c') . "\nConsent: ticked\nIP: " . $ip . "\nSaved to file: " . ($saved ? 'yes' : ($known ? 'already known' : 'no')) . "\n", $h, '-fcontact@novopix.uk');
// potwierdzenie dla subskrybenta z informacja, jak sie wypisac
$subj = $lang === 'pl' ? 'NovoPix: zapis na newsletter' : 'NovoPix: newsletter subscription';
$txt = $lang === 'pl'
  ? "Dziękujemy za zapis na newsletter NovoPix.\nBędziemy pisać rzadko: o nowych aplikacjach, grach i ofertach.\nAby się wypisać, odpowiedz na tę wiadomość słowem WYPISZ. Dane: https://novopix.uk/polityka/"
  : "Thank you for subscribing to the NovoPix newsletter.\nWe will write rarely: about new apps, games and offers.\nTo unsubscribe, reply to this message with the word UNSUBSCRIBE. Privacy: https://novopix.uk/privacy/";
@mail($email, '=?UTF-8?B?' . base64_encode($subj) . '?=', $txt, $h . "Reply-To: contact@novopix.uk\r\n", '-fcontact@novopix.uk');
if (!$saved && !$known && !$sentOwner) out(500, false, 'fail');
out(200, true);
