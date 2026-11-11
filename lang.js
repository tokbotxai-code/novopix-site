/* NovoPix language switcher: remembers an explicit EN/PL choice; never redirects crawlers or automation. */
(function () {
  var d = document, cur = (d.documentElement.getAttribute('lang') || 'en').slice(0, 2).toLowerCase();
  function get() { try { return localStorage.getItem('np_lang'); } catch (e) { return null; } }
  function set(v) { try { localStorage.setItem('np_lang', v); } catch (e) {} }
  d.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('.lsw a') : null;
    if (a) set((a.getAttribute('hreflang') || '').slice(0, 2));
  });
  var ua = navigator.userAgent || '';
  var bot = navigator.webdriver || /bot|crawl|spider|slurp|bing|google|yandex|duckduck|baidu|facebookexternalhit|preview|lighthouse|headless|pagespeed/i.test(ua);
  var pref = get();
  if (bot || (pref !== 'en' && pref !== 'pl') || pref === cur || /[?&]nolang\b/.test(location.search)) return;
  var t = d.querySelector('.lsw a[hreflang="' + pref + '"]');
  if (t && t.getAttribute('href')) location.replace(t.getAttribute('href') + location.hash);
})();
