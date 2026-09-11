# com_fgreports — súhrn opráv z bezpečnostnej/funkčnej analýzy (v1.5.0 → v1.7.3)

Toto zhŕňa sériu opráv vykonaných na základe externej analýzy kódu,
bod po bode. Podrobné technické detaily ku každej verzii sú v `CHANGELOG.md`
— toto je len prehľadová mapa "bod analýzy → verzia → čo sa stalo".

| # | Téma | Verzia | Výsledok |
|---|------|--------|----------|
| 1 | Cache TTL bol 60× dlhší než nastavenie (`setLifeTime()` berie minúty, nie sekundy) | **1.5.1** | Opravené — odstránené nesprávne `* 60` |
| 2 | Zmena SQL v reporte mohla vrátiť starý cachovaný výsledok (statické cache ID) | **1.5.2** | Opravené — cache ID teraz obsahuje hash zo `sql_script` + `modified`; navyše sa pri Uložení čistí celá cache skupina |
| 4 | Chýbal timeout samotného SQL dotazu (len connection timeout existoval) | **1.5.3** | Doplnené — nové nastavenie "Query timeout", `PDO::SQLSRV_ATTR_QUERY_TIMEOUT` |
| 5 | `ScriptGuard` sa uplatňoval nekonzistentne (len Preview) a dával falošný pocit bezpečia | **1.5.4** | Prerobené na nezáväzné upozornenie, zobrazené konzistentne pri Uložení aj Preview; rozšírený zoznam slov; README posilnené o dôraz na read-only DB login |
| 6 | `core.create`/`core.edit` de facto = prístup k interaktívnej SQL konzole cez Preview | **1.6.0** | Aktivované samostatné ACL právo `fgreports.execute` |
| 7 | `fgreports.execute` existovalo, ale nič nekontrolovalo | — | Vyriešené bodom 6 (rovnaká zmena) |
| 8 | "Predvolená cache" v Options bola mŕtve nastavenie (nový report vždy dostal 0) | **1.6.1** | Opravené — nový report teraz preberá `default_cache_ttl` z Options |
| 9 | Zoznam reportov na frontende mohol byť potichu skrátený globálnym Joomla `list_limit` | **1.6.2** | Opravené — `list.limit` natvrdo na 0 (bez limitu) |
| 10 | Pole `ordering` bolo nepoužiteľné (readonly, žiadne drag&drop) | **1.7.0** | Doplnené klasické Joomla drag-and-drop radenie v admin zozname |
| 11 | Cache reportu = druhá fyzická kópia dát mimo MS SQL (info, nie bug) | **1.7.1** | Zdokumentované v popise poľa aj v README |
| 12 | Manifest obsahoval zbytočnú MS SQL Server schému pre samotnú Joomlu (J4+ to nepodporuje) | **1.7.2** | Odstránené `sqlsrv`/`sqlazure` súbory a manifest entries |
| 13 | Chýbala PostgreSQL schéma pre samotnú Joomlu | **1.7.3** | Vedomé rozhodnutie: explicitne zadeklarované "len MySQL/MariaDB", PostgreSQL nepodporovaný |
| 15 | Custom field `cryptpassword` by šiel modernizovať na namespaced (`addfieldprefix`) | — | **Ponechané tak, ako je** — vedomé rozhodnutie kvôli predchádzajúcej skúsenosti s podobným zlyhaním v inom projekte; P3, bez funkčného rizika |
| 16 | JS náhľadu presunúť z inline `<script>` do `media/com_fgreports` + Web Asset Manager | — | **Ponechané tak, ako je** — kozmetické, bez funkčného rizika, admin-only obrazovka |
| 17 | Routing/alias-based URL namiesto `id` v query stringu | — | **Poznámka do budúcna** — vyžaduje vlastnú router triedu, robiť ako samostatnú úlohu |

## Aktuálny stav
- **Nainštalovaná/odporúčaná verzia: v1.7.3**
- Body 3 a 14 v pôvodnom číslovaní analýzy neboli poslané/neexistovali ako samostatné položky.
- Tri body (15, 16, 17) zostali vedome nezmenené — dôvody sú uvedené vyššie a v chatovej histórii pri danom bode.

## Odporúčanie pre teba
Po nasadení v1.7.3:
1. Over si oprávnenia MS SQL loginu v Options — read-only na konkrétnych
   reportovacích views/tabuľkách je stále jediná skutočná bezpečnostná
   hranica (nie `ScriptGuard`).
2. Ak máš v niektorom reporte zapnutú cache, over si, či report neobsahuje
   citlivé dáta, ktoré by si nechcel mať duplikované v Joomla cache.
3. Ak plánuješ pridať ďalších adminov/editorov (nie Super User), skontroluj
   pridelenie `fgreports.execute` v Users → Access Levels.
