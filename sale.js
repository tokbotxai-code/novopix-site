/* Black Friday 2026: -50% on all NovoPix prices, shown only inside the window (UK time = UTC in November).
   Test or preview: add ?sale=1 to the address (?sale=0 forces it off). Change the three constants below to move the dates. */
(function(){
var START=Date.parse('2026-11-24T00:00:00Z'),END=Date.parse('2026-12-01T00:00:00Z'),RATE=.5;
var q=location.search,now=Date.now();
var on=/[?&]sale=1/.test(q)||(now>=START&&now<END&&!/[?&]sale=0/.test(q));
if(!on||document.documentElement.dataset.saleDone)return;
document.documentElement.dataset.saleDone='1';
var pl=(document.documentElement.lang||'en').slice(0,2)==='pl';
function fmt(v){var h=Math.round(v*100*RATE)/100,i=Math.floor(h),c=Math.round((h-i)*100),ip=String(i);
 if(pl){ip=ip.replace(/\B(?=(\d{3})+(?!\d))/g,'\u00a0');return ip+(c?','+(c<10?'0':'')+c:'')+'\u00a0zł'}
 ip=ip.replace(/\B(?=(\d{3})+(?!\d))/g,',');return '£'+ip+(c?'.'+(c<10?'0':'')+c:'')}
var RE=pl?/(\d{1,3}(?:[ \u00a0\u202f]\d{3})+|\d+)(?:,(\d{2}))?([ \u00a0])zł/g:/£(\d{1,3}(?:,\d{3})+|\d+)(?:\.(\d{2}))?/g;
function num(m){var n=m[1].replace(/[ ,\u00a0\u202f]/g,'');return parseFloat(n+(m[2]?'.'+m[2]:''))}
var SKIP={SCRIPT:1,STYLE:1,NOSCRIPT:1,TEXTAREA:1,TITLE:1,S:1};
function walk(n){
 if(n.nodeType===3){var t=n.nodeValue;RE.lastIndex=0;if(!RE.test(t))return;RE.lastIndex=0;
  var f=document.createDocumentFragment(),last=0,m;
  while((m=RE.exec(t))){f.appendChild(document.createTextNode(t.slice(last,m.index)));
   var s=document.createElement('s');s.className='sale-was';s.textContent=m[0].replace(/[ \u202f]/g,'\u00a0');f.appendChild(s);
   var b=document.createElement('span');b.className='sale-now';b.textContent='\u00a0'+fmt(num(m));f.appendChild(b);last=m.index+m[0].length}
  f.appendChild(document.createTextNode(t.slice(last)));n.parentNode.replaceChild(f,n);return}
 if(n.nodeType!==1||SKIP[n.tagName]||n.hasAttribute('data-nosale')||(n.classList&&(n.classList.contains('sale-bar')||n.classList.contains('sale-note'))))return;
 var c=[].slice.call(n.childNodes);for(var i=0;i<c.length;i++)walk(c[i])}
walk(document.body);
var css=document.createElement('style');
css.textContent='.sale-bar{position:fixed;top:0;left:0;right:0;z-index:70;height:36px;display:flex;align-items:center;justify-content:center;gap:12px;padding:0 12px;background:linear-gradient(90deg,#ffc233,#ff8a5c 55%,#ff5a8a);color:#1a0f2e;font:700 13px/1.2 Inter,system-ui,sans-serif;text-align:center}.sale-bar a{color:inherit;text-decoration:underline;white-space:nowrap}body{padding-top:36px!important}header{top:36px!important}.sale-was{white-space:nowrap;opacity:.55;text-decoration:line-through;font-weight:400}.prn .sale-was,.price .sale-was,.pr .sale-was,.from .sale-was{font-size:.62em!important;margin-right:.15em}.sale-now{white-space:nowrap;font:inherit!important;font-weight:700!important;letter-spacing:inherit!important;text-transform:none!important;color:#ffc233!important;margin:0!important;display:inline!important;padding:0!important}.prn .sale-now{font-size:.8em!important}.sale-note{max-width:900px;margin:18px auto;padding:12px 16px;border:1px solid rgba(255,194,51,.5);border-radius:12px;font-size:13px;line-height:1.5;color:#e9e4ff}@media(max-width:520px){.sale-bar{font-size:11.5px;gap:8px}}';
document.head.appendChild(css);
var bar=document.createElement('div');bar.className='sale-bar';bar.setAttribute('role','region');bar.setAttribute('aria-label',pl?'Promocja Black Friday':'Black Friday sale');
var onPricing=/\/(pricing|cennik)\//.test(location.pathname);
bar.innerHTML=pl?'<span>Black Friday: −50% na wszystkie ceny · 24–30 listopada</span>'+(onPricing?'':'<a href="/pl/cennik/">Cennik</a>'):'<span>Black Friday: 50% off all prices · 24–30 November</span>'+(onPricing?'':'<a href="/pricing/">See prices</a>');
document.body.insertBefore(bar,document.body.firstChild);
var note=document.createElement('p');note.className='sale-note';
note.textContent=pl?'Promocja Black Friday: −50% od cen na liście dla projektów potwierdzonych na piśmie od 24 do 30 listopada 2026. Opieka miesięczna: cena promocyjna przez pierwsze 3 miesiące, potem cena regularna. Ceny regularne są pokazane jako przekreślone. Ceny netto.':'Black Friday: 50% off the listed prices for projects confirmed in writing between 24 and 30 November 2026. Monthly care: the sale price applies for the first 3 months, then the regular price. Regular prices are shown crossed out. Prices exclude VAT.';
var cf=document.getElementById('cf');
if(cf&&!cf.querySelector('input[name=sale]')){
 var hid=document.createElement('input');hid.type='hidden';hid.name='sale';hid.value='bf2026';cf.appendChild(hid);
 var fn=document.createElement('p');fn.className='sale-note';fn.style.margin='10px 0';
 fn.textContent=pl?'Black Friday: wspomnij o tym w wiadomości. Cena promocyjna dotyczy projektów potwierdzonych na piśmie do 30 listopada.':'Black Friday: mention it in your message. The sale price applies to projects confirmed in writing by 30 November.';
 var sb=cf.querySelector('button[type=submit],button:not([type]),input[type=submit]');
 if(sb&&sb.parentNode)sb.parentNode.insertBefore(fn,sb);else cf.appendChild(fn)}
var host=document.querySelector('#pricing .w')||document.querySelector('main')||document.body;
if(onPricing||document.getElementById('pricing'))host.appendChild(note);
})();
