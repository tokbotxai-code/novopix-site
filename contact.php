<?php
// Formularz kontaktowy NovoPix: wysyla wiadomosc na contact@novopix.uk (wlasny skrypt, bez uslug zewnetrznych).
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
function out($code, $ok, $extra = []) { http_response_code($code); echo json_encode(array_merge(['ok' => $ok], $extra)); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, false);
if (!empty($_POST['website'])) out(200, true); // pulapka na boty
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$msg = trim((string)($_POST['message'] ?? ''));
if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || mb_strlen($msg) < 2 || mb_strlen($msg) > 4000 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) out(422, false);
// limit: 5 wiadomosci na godzine z jednego IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$f = sys_get_temp_dir() . '/np_contact_' . md5($ip);
$now = time(); $hits = [];
if (is_file($f)) { $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($f))), fn($t) => $now - $t < 3600); }
if (count($hits) >= 5) out(429, false);
$hits[] = $now; @file_put_contents($f, implode(',', $hits));
$clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
$subject = 'NovoPix: message from ' . $clean($name);
$body = $msg . "\n\n--\n" . $name . " <" . $email . ">\nIP: " . $ip . "\n";
$headers = "From: NovoPix <contact@novopix.uk>\r\nReply-To: " . $clean($email) . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$sent = @mail('contact@novopix.uk', '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-fcontact@novopix.uk');
if (!$sent) out(500, false);
out(200, true);
