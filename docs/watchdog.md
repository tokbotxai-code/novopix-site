# Strażnik usług (watchdog.php)

Co 5 min sprawdza 5 adresów (HTTP 200, timeout 10 s). Po 2 kolejnych porażkach usługi wysyła mail `[ALERT]` na contact@novopix.uk (raz), po odzyskaniu `[OK]`. Działa tylko z CLI; z WWW zwraca 403.

## Linia cron (cPanel → Cron Jobs)

```
*/5 * * * * /usr/local/bin/php /home/lrtwzfhleb/novopix.uk/watchdog.php >/dev/null 2>&1
```

## Kroki na telefonie
1. Zaloguj się do cPanel w Spaceship (przeglądarka, tryb „wersja na komputer”).
2. Otwórz **Advanced → Cron Jobs**.
3. W „Add New Cron Job” wybierz „Once Per Five Minutes (*/5 * * * *)” lub wpisz pola ręcznie.
4. W polu **Command** wklej: `/usr/local/bin/php /home/lrtwzfhleb/novopix.uk/watchdog.php >/dev/null 2>&1`
5. Zapisz („Add New Cron Job”). Pierwszy mail przyjdzie dopiero po 2 porażkach.

## Zużycie
Żądanie HEAD (przy 405/501 jeden GET z `Range: 0-0`), timeout 10 s. 5 usług × 288 kontroli/dobę = ok. 1440 żądań na dobę. Jedno to kilkaset bajtów nagłówków, czyli ok. 0,5–1 MB transferu na dobę łącznie (szacunek).

## Blokada dostępu z WWW
Plik `.htaccess` w repo blokuje `watchdog.php` z WWW (403, reguła `RewriteRule`), a sam skrypt i tak kończy pracę poza CLI. Ten sam `.htaccess` ustawia nagłówki bezpieczeństwa całej strony (HSTS, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`).
