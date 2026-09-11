# FG SQL Reports (com_fgreports)

![Version](https://img.shields.io/badge/version-1.7.5-blue)
![Joomla](https://img.shields.io/badge/Joomla-6.x-1a6877)
![License](https://img.shields.io/badge/license-GPL--2.0-orange)
![GitHub release](https://img.shields.io/github/v/release/ferino75/com_fgreports)

Natívny Joomla 6 komponent, ktorý zobrazuje reporty z MS SQL Server databázy
na frontende. Jeden SQL skript = jeden report, výsledok sa vykreslí ako
tabuľka.

Vytvorené pre nasadenie v rámci lokálnej siete: Joomla beží na jednom
lokálnom serveri (intranet), MS SQL databáza na inom lokálnom serveri.

## Požiadavky

- Joomla 6.x, **s Joomla samotnou na MySQL/MariaDB** (schéma komponentu je
  dodaná len v MySQL variante - PostgreSQL nie je momentálne podporovaný,
  keďže na to nemám ako otestovať)
- PHP 8.1+ s povoleným rozšírením **pdo_sqlsrv** (Microsoft Drivers for PHP
  for SQL Server - https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server)
- Sieťová dostupnosť MS SQL Servera z Joomla servera (port zvyčajne 1433)
- Odporúčané: samostatný MS SQL login s **len SELECT** oprávnením na
  tabuľkách/pohľadoch, ktoré chceš reportovať

Toto sa netýka samotného reportovaného MS SQL Servera (ten je vždy MS SQL,
bez ohľadu na to, na čom beží Joomla) - ide výlučne o databázu, v ktorej
Joomla ukladá vlastný obsah (`#__fgreports_reports` a všetko ostatné).

## Inštalácia

1. Nainštaluj `com_fgreports_v1.0.0.zip` cez Extensions → Manage → Install.
2. Choď do **Components → FG SQL Reports → Options** a vyplň pripojenie
   k MS SQL Serveru (server, port, databáza, login, heslo).
3. Tlačidlom **Test pripojenia** (v zozname reportov) over, že pripojenie
   funguje.
4. Vytvor nový report (Components → FG SQL Reports → New), vlož SQL skript
   (jeden `SELECT`), over ho tlačidlom **Spustiť náhľad**, nastav prístupovú
   úroveň a publikuj.
5. Na frontende pridaj menu položku typu **FG SQL Reports → Reports** (zoznam
   všetkých reportov, ktoré má prihlásený používateľ právo vidieť) alebo
   **Report** (konkrétny jeden report).

## Bezpečnosť - dôležité

Report sa spúšťa presne tak, ako je napísaný - žiadny query builder, žiadne
parsovanie. Skutočná ochrana pred nechceným zápisom/zmazaním dát je:

1. **Read-only MS SQL login** nastavený v Options - toto je **jediná skutočná**
   bezpečnostná hranica, nie čokoľvek v tomto komponente. Ideálne mu daj
   `SELECT` len na konkrétne reportovacie views/tabuľky, ktoré má vidieť -
   nie širokú rolu ako `db_datareader`, pokiaľ naozaj nechceš, aby report
   mal prístup ku každej tabuľke v databáze.
2. Úpravu/vytváranie reportov (teda priamy prístup k spúšťaniu ľubovoľného
   SQL kódu) má v Joomla ACL len skupina s `core.edit` / `core.create` na
   `com_fgreports` - v predvolenom nastavení Super Users. Prispôsob to v
   Users → Access Levels, ak chceš pustiť aj iné dôveryhodné skupiny.
3. Komponent pri ukladaní aj pri náhľade zobrazí **nezáväzné upozornenie**
   (nič neblokuje), ak skript obsahuje slová ako `INSERT`, `UPDATE`,
   `DELETE`, `DROP`, `ALTER`, `EXEC`, `BACKUP`, `DBCC`, `WAITFOR`, `INTO`
   a pod. Toto je len rýchly heads-up pre admina, **nie bezpečnostná
   kontrola** - má falošné poplachy (napr. `SELECT 'UPDATE' AS Operation`)
   aj diery (nezachytí napr. `SELECT ... INTO novatabuľka` vždy spoľahlivo,
   ani ďalšie desiatky spôsobov, ako zapísať dáta). Neignoruj bod 1 kvôli
   tomu, že toto upozornenie ostalo ticho.
4. Heslo k databáze sa v `#__extensions` ukladá **šifrované** (libsodium,
   kľúč odvodený z `$secret` v `configuration.php`), nie v čistom texte. V
   poli Heslo v Options nikdy neuvidíš uloženú hodnotu - pole je vždy
   prázdne; necháš ho prázdne, ak chceš zachovať aktuálne heslo, alebo doň
   napíšeš nové. Pozor: ak sa niekedy zmení `$secret` (napr. pri ručnom
   zásahu do `configuration.php`), staré heslo sa už nedá dešifrovať a treba
   ho v Options zadať znova.

## Cache

Každý report má vlastné pole **Cache (minúty)**. `0` = dopyt sa spustí pri
každom zobrazení stránky. Iná hodnota = výsledok sa na danú dobu uloží do
Joomla cache (skupina `com_fgreports`), takže sa databáza nezaťažuje pri
každej návšteve.

**Dôležité:** zapnutá cache znamená, že výsledok reportu sa fyzicky uloží
ako druhá kópia dát - v Joomla cache (súbor na disku, databáza alebo Redis,
podľa toho, ako máš v Joomle nastavené úložisko cache), nie len v MS SQL
Serveri. Pre reporty s citlivejšími údajmi, ktoré nechceš mať duplikované
mimo zdrojovej databázy, nechaj Cache na `0`.

## Prístupové úrovne (ACL)

Každý report má štandardné Joomla pole **Access** (Public / Registered /
Special / vlastné úrovne). Na frontende sa v zozname aj pri priamom
zobrazení reportu vždy kontroluje `getAuthorisedViewLevels()` prihláseného
používateľa.

## Známe obmedzenia / plán do budúcna

- Reporty zatiaľ nepodporujú parametre/filtre zadávané na frontende (napr.
  dátumový rozsah) - stĺpec `params` v tabuľke `#__fgreports_reports` je na
  to už pripravený, takže pridanie tejto funkcie v budúcnosti nebude
  vyžadovať zmenu schémy.
- Podporované je len jedno globálne pripojenie k MS SQL (spoločné pre
  všetky reporty).
- Vracia sa len prvý result set skriptu.

## Licencia

GNU General Public License version 2 or later - pozri LICENSE.txt.
