<?php
// NovoPix: public code activity for the home page ("By the numbers").
// Counts commits per day (last 7 days, Europe/London) in our public GitHub repositories.
// No tokens, public API only. The result is cached for 12 hours in the temp directory.
// On any failure the answer is {"ok":false} and the page hides the chart (no stale numbers).
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');

$OWNER = 'tokbotxai-code';
$REPOS = ['pepsafe' => 'Pepsafe', 'pantrypal' => 'pantrypal', 'site' => 'novopix-site', 'apps' => 'novopix-apps'];
$TTL = 43200;   // 12 h: how long a successful result is served
$RETRY = 900;   // 15 min: pause after a failed download before trying again
$API = getenv('ACTIVITY_API') ?: 'https://api.github.com'; // override for tests only
$dir = rtrim(getenv('ACTIVITY_DIR') ?: sys_get_temp_dir(), '/');
$cacheFile = $dir . '/novopix-activity-v1.json';
$failFile = $dir . '/novopix-activity-v1.fail';
$lockFile = $dir . '/novopix-activity-v1.lock';

function np_out($json, $maxAge) {
  header('Cache-Control: ' . ($maxAge > 0 ? 'public, max-age=' . $maxAge : 'no-store'));
  echo $json;
  exit;
}
function np_fresh($file, $ttl) {
  if (!is_file($file) || time() - (int)@filemtime($file) >= $ttl) return null;
  $raw = (string)@file_get_contents($file);
  $d = json_decode($raw, true);
  return (is_array($d) && !empty($d['ok'])) ? $raw : null;
}
function np_get($url) {
  if (!function_exists('curl_init')) return null;
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_USERAGENT => 'novopix.uk-activity/1.0',
    CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'],
  ]);
  $body = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  if ($code !== 200 || !is_string($body)) return null;
  $d = json_decode($body, true);
  return is_array($d) ? $d : null;
}

$hit = np_fresh($cacheFile, $TTL);
if ($hit !== null) np_out($hit, 1800);
if (is_file($failFile) && time() - (int)@filemtime($failFile) < $RETRY) np_out('{"ok":false}', 0);

// One download at a time: other visitors get "no data" for the few seconds it takes.
$lock = @fopen($lockFile, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) np_out('{"ok":false}', 0);
$hit = np_fresh($cacheFile, $TTL);
if ($hit !== null) np_out($hit, 1800);

$tz = new DateTimeZone('Europe/London');
$now = new DateTimeImmutable('now', $tz);
$start = $now->setTime(0, 0)->modify('-6 days');
$since = $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
$days = [];
for ($i = 0; $i < 7; $i++) $days[$start->modify('+' . $i . ' days')->format('Y-m-d')] = 0;
$repos = [];
$ok = true;
foreach ($REPOS as $key => $repo) {
  $n = 0;
  for ($page = 1; $page <= 5; $page++) {
    $list = np_get($API . '/repos/' . $OWNER . '/' . $repo . '/commits?since=' . rawurlencode($since) . '&per_page=100&page=' . $page);
    if ($list === null) { $ok = false; break 2; }
    foreach ($list as $c) {
      $date = $c['commit']['committer']['date'] ?? ($c['commit']['author']['date'] ?? null);
      if (!is_string($date)) continue;
      try { $day = (new DateTimeImmutable($date))->setTimezone($tz)->format('Y-m-d'); } catch (Exception $e) { continue; }
      if (!isset($days[$day])) continue;
      $days[$day]++;
      $n++;
    }
    if (count($list) < 100) break;
  }
  $repos[] = ['id' => $key, 'count' => $n];
}
if (!$ok) {
  @touch($failFile);
  np_out('{"ok":false}', 0);
}
$outDays = [];
foreach ($days as $d => $n) $outDays[] = ['date' => $d, 'count' => $n];
$json = json_encode(['ok' => true, 'updated' => gmdate('Y-m-d\TH:i:s\Z'), 'total' => array_sum($days), 'days' => $outDays, 'repos' => $repos]);
$tmp = $cacheFile . '.' . getmypid() . '.tmp';
if (@file_put_contents($tmp, $json) !== false) @rename($tmp, $cacheFile);
@unlink($failFile);
np_out($json, 1800);
