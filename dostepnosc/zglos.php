<?php
// Darmowa kontrola dostepnosci: przyjmuje zgloszenie, skanuje strone skanerem NovoPix i wysyla raport na e-mail
// zgloszeniodawcy (kopia na contact@novopix.uk). Wlasny skrypt, bez uslug zewnetrznych.
const OWNER = 'contact@novopix.uk';
const SCANNER = 'https://scanner.novopix.uk/api/scan';

function out($code, $ok) { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok' => $ok]); exit; }
function clean($s) { return str_replace(["\r", "\n"], ' ', (string)$s); }
function send($to, $subject, $body, $replyTo = null) {
    $h = "From: NovoPix <" . OWNER . ">\r\n" . ($replyTo ? "Reply-To: " . clean($replyTo) . "\r\n" : '') . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $h, '-f' . OWNER);
}
// Audyty Lighthouse zwiazane z dostepnoscia (id) + polski opis
function a11y_map() {
    return [
        'color-contrast' => 'Zbyt niski kontrast tekstu',
        'image-alt' => 'Obrazy bez tekstu alternatywnego',
        'label' => 'Pola formularza bez etykiet',
        'button-name' => 'Przyciski bez nazwy',
        'link-name' => 'Linki bez nazwy',
        'link-text' => 'Linki bez opisowego tekstu',
        'html-has-lang' => 'Brak określonego języka strony',
        'html-lang-valid' => 'Niepoprawny język strony',
        'document-title' => 'Brak tytułu strony',
        'heading-order' => 'Zaburzona kolejność nagłówków',
        'landmark-one-main' => 'Brak głównego obszaru strony (main)',
        'bypass' => 'Brak sposobu pominięcia powtarzalnych bloków',
        'tabindex' => 'Nieprawidłowe użycie tabindex',
        'meta-viewport' => 'Zablokowane powiększanie na telefonie',
        'frame-title' => 'Ramki bez tytułu',
        'aria-allowed-attr' => 'Nieprawidłowe atrybuty ARIA',
        'aria-required-attr' => 'Brakujące atrybuty ARIA',
        'aria-valid-attr-value' => 'Nieprawidłowe wartości ARIA',
        'target-size' => 'Zbyt małe elementy dotykowe',
        'select-name' => 'Listy wyboru bez nazwy',
        'duplicate-id-aria' => 'Powtórzone identyfikatory ARIA',
        'list' => 'Niepoprawna struktura list',
        'listitem' => 'Elementy list poza listą',
    ];
}
function build_report($d, $url) {
    $st = $d['measurementStatus'] ?? '';
    $cat = $d['categories'] ?? [];
    $fmt = fn($v) => is_numeric($v) ? ((int)$v . '/100') : 'brak pomiaru';
    $L = [];
    $L[] = "Dzień dobry,";
    $L[] = "";
    $L[] = "oto wstępna, automatyczna kontrola dostępności strony: " . $url;
    $L[] = "";
    if ($st === 'live_lighthouse') {
        $L[] = "Wyniki (silnik Google Lighthouse):";
        $L[] = " - Dostępność: " . $fmt($cat['accessibility'] ?? null);
        $L[] = " - Wydajność: " . $fmt($cat['performance'] ?? null);
        $L[] = " - SEO: " . $fmt($cat['seo'] ?? null);
        $L[] = " - Dobre praktyki: " . $fmt($cat['bestPractices'] ?? null);
    } else {
        $L[] = "Pełny pomiar Lighthouse był chwilowo niedostępny, więc nie podajemy wyniku dostępności (nie zgadujemy). Poniżej to, co udało się zmierzyć w kodzie strony.";
        if (isset($cat['seo']) && is_numeric($cat['seo'])) $L[] = " - SEO: " . $fmt($cat['seo']);
    }
    $map = a11y_map(); $found = [];
    foreach (($d['issues'] ?? []) as $i) { $id = $i['id'] ?? ''; if (isset($map[$id])) $found[$id] = $map[$id]; }
    $L[] = "";
    if ($found) {
        $L[] = "Wykryte problemy związane z dostępnością:";
        foreach ($found as $t) $L[] = " - " . $t;
    } elseif ($st === 'live_lighthouse') {
        $L[] = "Automat nie wykrył typowych błędów dostępności. To nie znaczy, że strona jest dostępna: klawiaturę, czytniki ekranu i treść trzeba sprawdzić ręcznie.";
    }
    $L[] = "";
    $L[] = "Ważne: to wstępna kontrola automatyczna jednej strony. Narzędzia znajdują tylko część problemów. Raport nie jest certyfikatem zgodności, deklaracją dostępności ani poradą prawną.";
    $L[] = "";
    $L[] = "Chcesz pełny audyt (ręczne testy z klawiaturą i czytnikiem ekranu, lista poprawek, pomoc w deklaracji dostępności)? Odpisz na tego maila.";
    $L[] = "";
    $L[] = "Otrzymujesz tę wiadomość, bo wyrażono zgodę na przesłanie raportu i informacji o usługach NovoPix. Zgodę możesz wycofać w każdej chwili, odpisując na ten adres. Polityka prywatności: https://novopix.uk/polityka/";
    $L[] = "";
    $L[] = "NovoPix · contact@novopix.uk";
    return implode("\n", $L);
}
function run_scan($url) {
    $ch = curl_init(SCANNER);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 170, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode(['url' => $url])]);
    $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $d = $r ? json_decode($r, true) : null;
    return ($code === 200 && is_array($d)) ? $d : null;
}

if (defined('ZGLOS_TEST')) return; // tryb testowy: tylko funkcje

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false);
$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d)) $d = $_POST;
if (!empty($d['_honey'])) out(200, true);
$url = trim((string)($d['adres_strony'] ?? '')); $org = trim((string)($d['placowka'] ?? '')); $email = trim((string)($d['email'] ?? ''));
if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
$host = strtolower((string)parse_url($url, PHP_URL_HOST));
if (($d['zgoda'] ?? '') !== 'tak' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120
    || !filter_var($url, FILTER_VALIDATE_URL) || strlen($url) > 300 || strlen($org) > 200
    || $host === '' || strpos($host, '.') === false || filter_var($host, FILTER_VALIDATE_IP) || preg_match('/(^|\.)(localhost|local|internal|lan)$/', $host)) out(422, false);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0'; $f = sys_get_temp_dir() . '/np_zglos_' . md5($ip); $now = time(); $hits = [];
if (is_file($f)) { $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($f))), fn($t) => $now - $t < 3600); }
if (count($hits) >= 3) out(429, false);
$hits[] = $now; @file_put_contents($f, implode(',', $hits));

// 1) powiadomienie dla wlasciciela od razu
send(OWNER, 'Darmowy skan dostepnosci: nowe zgloszenie',
    "Adres strony: " . clean($url) . "\nPlacowka: " . clean($org) . "\nE-mail: " . clean($email) . "\nZgoda na raport i informacje o uslugach NovoPix: tak\nIP: $ip\n", $email);

// 2) odpowiedz od razu, raport w tle
ignore_user_abort(true); @set_time_limit(200);
http_response_code(200); header('Content-Type: application/json; charset=utf-8'); header('Connection: close');
$body = json_encode(['ok' => true]); header('Content-Length: ' . strlen($body)); echo $body;
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
else { @ob_end_flush(); @flush(); }

$scan = run_scan($url);
if ($scan) {
    $rep = build_report($scan, $url);
    send($email, 'Raport dostępności strony: ' . $host, $rep, OWNER);
    send(OWNER, 'Wyslano raport: ' . $host, "Raport wyslany do: $email\n\n" . $rep);
} else {
    send($email, 'Twoje zgłoszenie kontroli dostępności: ' . $host, "Dziękujemy za zgłoszenie. Automatyczna kontrola strony $url nie powiodła się (strona może blokować roboty albo być chwilowo niedostępna). Sprawdzimy ją ręcznie i odezwiemy się na ten adres.\n\nNovoPix · " . OWNER, OWNER);
    send(OWNER, 'Skan nieudany, trzeba odpisac recznie: ' . $host, "Zgloszenie: $url\nE-mail: $email\nSkaner nie zwrocil wyniku.");
}
