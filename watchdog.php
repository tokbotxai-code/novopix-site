<?php
// External service watchdog. Run from cron (CLI only).
if (php_sapi_name() !== 'cli') { http_response_code(403); exit; }

$test = getenv('WATCHDOG_TEST') === '1';
$services = [
  'novopix.uk' => 'https://novopix.uk/',
  'scanner' => 'https://scanner.novopix.uk/api/health',
  'panel' => 'https://panel.novopix.uk/api/health',
  'fridgebite' => 'https://fridgebite.novopix.uk/api/health',
  'pantrypal' => 'https://pantrypal.novopix.uk/',
];
$override = getenv('WATCHDOG_URLS'); // "name=url,name=url" (tests)
if ($override) {
  $services = [];
  foreach (explode(',', $override) as $p) { $kv = explode('=', $p, 2); if (count($kv) === 2) $services[trim($kv[0])] = trim($kv[1]); }
}
$stateFile = getenv('WATCHDOG_STATE') ?: sys_get_temp_dir() . '/novopix-watchdog-state.json';
$mailLog = getenv('WATCHDOG_MAILLOG'); // test mode: append mails here instead of sending
$to = 'contact@novopix.uk';

$state = [];
if (is_file($stateFile)) { $d = json_decode((string)file_get_contents($stateFile), true); if (is_array($d)) $state = $d; }

function probe($url, $head) {
  $ch = curl_init($url);
  $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'NovoPix-Watchdog/1.0'];
  if ($head) $o[CURLOPT_NOBODY] = true; else $o[CURLOPT_RANGE] = '0-0';
  curl_setopt_array($ch, $o);
  curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  return $code;
}
function check($url) {
  $code = probe($url, true);
  if ($code === 405 || $code === 501) $code = probe($url, false); // GET, 1 byte
  return $code === 200 || $code === 206;
}
function notify($to, $subject, $body, $mailLog) {
  if ($mailLog) { file_put_contents($mailLog, "To: $to\nSubject: $subject\n\n$body\n---\n", FILE_APPEND); return; }
  mail($to, $subject, $body, "From: contact@novopix.uk\r\nContent-Type: text/plain; charset=utf-8");
}

foreach ($services as $name => $url) {
  $s = $state[$name] ?? ['fails' => 0, 'alerted' => false];
  if (check($url)) {
    if (!empty($s['alerted'])) notify($to, "[OK] usługa $name wróciła", "Usługa $name ($url) odpowiada ponownie (HTTP 200).", $mailLog);
    $s = ['fails' => 0, 'alerted' => false];
  } else {
    $s['fails'] = (int)$s['fails'] + 1;
    if ($s['fails'] >= 2 && empty($s['alerted'])) {
      notify($to, "[ALERT] usługa $name nie odpowiada", "Usługa $name ($url) nie zwróciła HTTP 200 w {$s['fails']} kolejnych kontrolach.", $mailLog);
      $s['alerted'] = true;
    }
  }
  $state[$name] = $s;
}
file_put_contents($stateFile, json_encode($state), LOCK_EX);
