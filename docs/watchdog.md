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

## Blokada dostępu z WWW
W repo nie ma pliku `.htaccess`, więc go nie dodano. Jeśli istnieje na serwerze w `/home/lrtwzfhleb/novopix.uk/.htaccess`, dopisz na końcu (kod w pliku i tak zwraca 403 poza CLI):

```
<Files "watchdog.php">
Require all denied
</Files>
```
