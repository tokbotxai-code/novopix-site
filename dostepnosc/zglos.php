<?php
// Zgloszenia darmowej kontroli dostepnosci: mail na contact@novopix.uk (wlasny skrypt).
header('Content-Type: application/json; charset=utf-8');
function out($code, $ok) { http_response_code($code); echo json_encode(['ok' => $ok]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false);
$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d)) $d = $_POST;
if (!empty($d['_honey'])) out(200, true);
$url = trim((string)($d['adres_strony'] ?? '')); $org = trim((string)($d['placowka'] ?? '')); $email = trim((string)($d['email'] ?? ''));
if (($d['zgoda'] ?? '') !== 'tak' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120 || strlen($url) < 4 || strlen($url) > 300 || strlen($org) > 200) out(422, false);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0'; $f = sys_get_temp_dir() . '/np_zglos_' . md5($ip); $now = time(); $hits = [];
if (is_file($f)) { $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($f))), fn($t) => $now - $t < 3600); }
if (count($hits) >= 5) out(429, false);
$hits[] = $now; @file_put_contents($f, implode(',', $hits));
$c = fn($s) => str_replace(["\r", "\n"], ' ', $s);
$body = "Adres strony: " . $c($url) . "\nPlacowka: " . $c($org) . "\nE-mail: " . $c($email) . "\nZgoda na raport i informacje o uslugach NovoPix: tak\nIP: $ip\n";
$h = "From: NovoPix <contact@novopix.uk>\r\nReply-To: " . $c($email) . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$ok = @mail('contact@novopix.uk', '=?UTF-8?B?' . base64_encode('Darmowy skan dostepnosci: nowe zgloszenie') . '?=', $body, $h, '-fcontact@novopix.uk');
out($ok ? 200 : 500, (bool)$ok);
