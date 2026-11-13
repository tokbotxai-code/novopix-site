/* NovoPix: anonymous page-view counter. No cookies, no stored IP addresses, only daily totals. Respects Do Not Track. */
(function () {
  try {
    var n = navigator, d = document;
    if (n.webdriver || n.doNotTrack === '1' || window.doNotTrack === '1' || n.globalPrivacyControl) return;
    if (!/(^|\.)novopix\.uk$/.test(location.hostname)) return;
    var body = JSON.stringify({ path: location.pathname, ref: d.referrer || '', lang: (d.documentElement.lang || '').slice(0, 2) });
    var url = 'https://scanner.novopix.uk/api/hit';
    if (!(n.sendBeacon && n.sendBeacon(url, body))) fetch(url, { method: 'POST', body: body, keepalive: true, mode: 'cors', credentials: 'omit' }).catch(function () {});
  } catch (e) {}
})();
