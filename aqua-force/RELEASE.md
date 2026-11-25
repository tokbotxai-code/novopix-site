# NovoJet: wydanie w Google Play (instrukcja)

Stan: gra jest PWA (manifest, ikony, service worker, działa offline, zero zewnętrznych zapytań), ma politykę prywatności i komplet materiałów do sklepu w `store/`. Brakuje tylko rzeczy wymagających konta właściciela (punkty 1–2 niżej).

## 1. Konto (robi właściciel)
- Konto Google Play Console (jednorazowa opłata 25 USD) i weryfikacja tożsamości.

## 2. Paczka Androida: wybierz jedną drogę
**A. PWABuilder (najprostsza, w przeglądarce, bez Android Studio):**
1. Wejdź na https://www.pwabuilder.com i wpisz https://novopix.uk/aqua-force/ .
2. Wybierz „Package for stores → Android”, nazwa pakietu `uk.novopix.aquaforce`, zapisz wygenerowany plik podpisu (keystore) w bezpiecznym miejscu (bez niego nie wydasz aktualizacji).
3. Pobierz plik `.aab` i dołóż do Play Console. UWAGA: TWA wymaga pliku `https://novopix.uk/.well-known/assetlinks.json` z odciskiem SHA-256 klucza podpisu. Plik ten dodaję ja po podaniu odcisku (z Play Console: Konfiguracja → Integralność aplikacji → klucz podpisu aplikacji).

**B. Capacitor (gra spakowana w aplikację, bez weryfikacji domeny, działa w pełni offline):**
1. Na komputerze z Android Studio: `npm init -y && npm i @capacitor/core @capacitor/cli @capacitor/android`, `npx cap init "NovoJet" uk.novopix.aquaforce --web-dir www`, skopiuj zawartość tego folderu (bez `store/`) do `www/`, `npx cap add android`, `npx cap sync`.
2. W Android Studio: Build → Generate Signed Bundle (AAB), targetSdk zgodny z aktualnym wymogiem Play.
3. Ikonę aplikacji ustaw z `icon-512.png` (adaptacyjna: `icon-maskable-512.png`).

## 3. Karta sklepu
- Teksty EN/PL, kategorie i odpowiedzi do formularzy: `store/LISTING.md`.
- Grafiki: `store/icon-512.png`, `store/feature-graphic-1024x500.png`, zrzuty `store/screenshot-*.png`.
- Adres polityki prywatności: https://novopix.uk/aqua-force/privacy.html

## 4. Testy przed publikacją
- Play wymaga zwykle testu zamkniętego (ok. 12 testerów przez 14 dni) dla nowych kont osobistych. Konto organizacji (firmowe) bywa zwolnione. Sprawdź w Play Console, które wymagania dotyczą Twojego konta.

## 5. Aktualizacje gry
Po zmianie `index.html` podbij `CACHE` w `sw.js` (np. `aquaforce-v2`), żeby przeglądarka i TWA pobrały nową wersję.
