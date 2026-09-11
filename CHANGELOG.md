# Changelog

## 1.7.6
- Added an update server: `updates.xml` at the repo root plus `<updateservers>` in the manifest, pointing at the raw file on the `master` branch (same pattern as the other FG extensions - update site XML with `<element>com_fgreports</element>` (components keep the `com_` prefix, unlike plugins), `<client>administrator</client>`, and a `targetplatform` regex scoped to `6\..*`). Once the GitHub repo/releases exist, Joomla's own Extension Manager can offer and install updates directly.
- Translated `README.md` from Slovak to English and brought it up to date with everything implemented since it was first written (pagination, sorting, drag-and-drop ordering, `fgreports.execute`, encrypted password, per-report `stack_mobile`/`table_css_class`, query timeout) - the original only covered the state as of the very first release.

## 1.7.5
- `LICENSE.txt` was referenced by the manifest's `<license>` tag ("see LICENSE.txt") but was never actually included in the installable package - it only existed in the repo root, which isn't part of the install ZIP. Copied it into `admin/LICENSE.txt` and added it to the manifest's admin `<files>` list, so it's now physically present in the installed extension.

## 1.7.4
- Menu item type metadata (`site/tmpl/reports/default.xml`, `site/tmpl/report/default.xml`) had the layout `title`/`message` hardcoded in Slovak, even though the component ships a full `en-GB` translation. Switched both to language keys (`COM_FGREPORTS_VIEW_REPORTS_TITLE`/`_DESC`, `COM_FGREPORTS_VIEW_REPORT_TITLE`/`_DESC`), matching the exact convention core uses for its own menu item types (e.g. `COM_CONTENT_ARTICLE_VIEW_DEFAULT_TITLE`).

## 1.7.3
- Documentation only: explicitly declared the supported Joomla CMS database platform as MySQL/MariaDB (no PostgreSQL schema is provided/tested). Updated the README requirements and the install-screen description. No PostgreSQL schema was ever included, so this doesn't change behavior - it just makes the existing limitation explicit up front instead of failing silently on a PostgreSQL-based Joomla install.

## 1.7.2
- Removed the MS SQL Server (`sqlsrv`/`sqlazure`) variants of `admin/sql/install`, `admin/sql/uninstall` and `admin/sql/updates` from the manifest and deleted the corresponding files. These were for Joomla's *own* CMS database, not the external MS SQL Server this component reports on - and Joomla 4+ (so all of 5.x/6.x) dropped SQL Server as a supported CMS database platform entirely (MySQL/MariaDB/PostgreSQL only), so those files could never actually run. The `mysql/` versions (still used, since Joomla itself runs on MySQL/MariaDB) and everything related to the external `pdo_sqlsrv` reporting connection are unchanged.

## 1.7.1
- Documentation only: made it explicit (in the "Cache (minutes)" field description and the README) that enabling cache on a report stores a second physical copy of its result in Joomla's own cache storage (file/database/Redis, depending on Joomla's cache configuration) - not just a re-fetch from MS SQL. Recommended leaving cache at `0` for reports containing sensitive data that shouldn't be duplicated outside the source database. No behavior change - `Cache = 0` (disabled) was already supported per report.

## 1.7.0
- Added proper drag-and-drop ordering to the admin Reports list, instead of a `readonly` `ordering` field that every report was stuck at `0` with (which then sorted by title regardless of the column's presence). Switch the "Sort by" dropdown to "Ordering" to reveal the drag handles; `AdminController::saveOrderAjax()`/`AdminModel::saveorder()` are already inherited from the base Joomla classes and needed no PHP changes - only the list template markup (drag handle column, `js-draggable` `<tbody>`, hidden `order[]` inputs) and a new "Ordering" option in the sort-by filter.

## 1.6.2
- Fixed the front-end reports list silently truncating to the site's global Joomla "List Limit" (Global Configuration, commonly 20) since it has no pagination UI at all and is documented as showing every published report the user may see. `Site\Model\ReportsModel::populateState()` now forces `list.limit` to `0` (no limit) - confirmed this is Joomla's standard way to request an unbounded result from a `ListModel` (`_getList()`/query builder treat `0` as "no `LIMIT` clause", same convention as a list's "All" option).

## 1.6.1
- Fixed "Default Cache (minutes)" in Options → Execution being dead: a brand-new report's `cache_ttl` always started at the form field's static `default="0"`, never at the configured global default. `Administrator\Model\ReportModel::getItem()` now overrides `cache_ttl` with `ComponentHelper::getParams('com_fgreports')->get('default_cache_ttl', 0)` when the item is a new (unsaved) record. Existing reports are unaffected.

## 1.6.0
- Activated the previously-reserved `fgreports.execute` ACL action for its intended purpose: gating the interactive SQL Preview endpoint separately from `core.edit`/`core.create`. Being able to create/edit a report and being able to interactively run arbitrary SQL against the reporting database right now (without saving anything) are different levels of trust - a site may want to let a group create/edit reports without letting them run ad-hoc queries via Preview. `ReportController::preview()` now requires both; the Preview button itself is only rendered in the edit form for users who already have `fgreports.execute`.
  - **Behavior change for non-Super-User accounts**: `fgreports.execute` is a brand-new action with no rules configured yet, so - like any fresh ACL action - it defaults to denied for everyone except Super Users (who bypass ACL entirely). If you have any non-Super-User accounts that currently use "Run Preview", grant them `fgreports.execute` in Users → Access Levels (or the relevant User Group's permissions for com_fgreports) after updating, or they'll lose access to that button until you do.

## 1.5.4
- Reworked `ScriptGuard` from an inconsistent hard block into an explicit, non-blocking advisory check, applied consistently everywhere a script is saved or previewed:
  - It only ran in the admin AJAX Preview before - never on Save, and never on the front end (which calls `ConnectionHelper::runScript()` directly) - so a report saved without ever clicking Preview had no check applied to it at all.
  - Even where it did run, keyword matching has real false positives (e.g. `SELECT 'UPDATE' AS Operation`, or a keyword inside a `--` comment) and false negatives (`SELECT ... INTO newtable`, `BACKUP`, `DBCC`, `WAITFOR`, `KILL`, `DENY` weren't in the list at all) - it cannot be a real security boundary.
  - `ReportController::preview()` no longer refuses to run a script over this; it runs it and includes a warning message alongside the (successful) result. `ReportTable::check()` now shows the same warning (via `enqueueMessage`, non-blocking) on every Save. Widened the keyword list to include `BACKUP`, `DBCC`, `WAITFOR`, `KILL`, `DENY`, `INTO`.
  - Strengthened the README and the field/warning text to state plainly that the read-only MS SQL login is the only real protection, and to specifically recommend granting `SELECT` on individual reporting views/tables rather than a broad role like `db_datareader` when the reporting login shouldn't see the whole database.

## 1.5.3
- Added a query timeout, separate from the existing connection timeout. `dbtimeout`/"Connection Timeout" only bounds how long establishing the connection (`LoginTimeout`) is allowed to take - once connected, a report's SQL script had no timeout at all, so a locked or slow query could hold a PHP request open indefinitely. Added a new "Query Timeout (seconds)" option (`dbquerytimeout`, default 30s) and set it via `PDO::SQLSRV_ATTR_QUERY_TIMEOUT` on the connection in `ConnectionHelper::getConnection()`, which - per Microsoft's PDO_SQLSRV docs - applies to any subsequent `PDO::query()`/`PDO::exec()` call on that connection.

## 1.5.2
- **Critical fix**: editing a report's SQL script could still serve the *old* script's cached results until the TTL naturally expired. `Joomla\CMS\Cache\Controller\CallbackController::get()` only auto-derives a cache ID from the callback+arguments (which would include the SQL text) when no explicit `$id` is given - `runWithCache()` passed a static `'report_' . $item->id` explicitly, so the cache key never changed when the SQL changed. Fixed by hashing the SQL script + the report's `modified` timestamp into the cache ID (`report_<id>_<sha256>`), so any save (SQL change or not, since `modified` always changes) produces a fresh cache key. Also added: `Administrator\Model\ReportModel::save()` now clears the whole `com_fgreports` cache group after a successful save, so old now-unreachable entries don't just sit on disk until they expire.

## 1.5.1
- **Critical fix**: report caching lasted 60× longer than configured. `Joomla\CMS\Cache\Cache::setLifeTime()` takes the lifetime in **minutes** (confirmed in `libraries/src/Cache/Cache.php`'s own docblock), but `ReportModel::runWithCache()` was calling `$cache->setLifeTime($ttl * 60)` - since the "Cache (minúty)" field is already in minutes, this passed minutes×60 as if it were minutes, i.e. the actual cache duration was `$ttl` **hours**, not `$ttl` minutes (1 min setting → cached 1 hour; 15 min → cached 15 hours; 1440 min/"1 day" setting → cached 60 days). Changed to `$cache->setLifeTime($ttl)`. If you had cache TTL configured on any report, its cached data was living far longer than intended - worth clearing Joomla's cache once after updating so stale results aren't served for the old (much longer) duration.

## 1.5.0
- Added front-end column sorting: clicking a column header sorts the full result set by that column (▲/▼ indicator, click again to reverse), resetting to page 1. Requested column is validated against the report's actual result columns (whitelist) before use - never passed into any query. Sorting happens in PHP on the same fully-fetched/cached array used for pagination, so a cached report still doesn't re-hit MS SQL just because someone re-sorts it. Comparison is numeric-aware for numeric values and natural-order, case-insensitive for text; `NULL` values always sort last regardless of direction.
- Fixed a latent bug in the pagination/sort link builders: `Uri::getInstance()` returns Joomla's shared singleton for the current request, so calling `setVar()` on it directly (as the pagination code did since 1.4.0) permanently mutated that shared object for the rest of the request - risking wrong URLs elsewhere on the page (canonical link, breadcrumbs, etc.) generated later in the same request. Both link builders now `clone` the instance before modifying it.

## 1.4.3
- Added an "Extra CSS trieda(y)" (Extra CSS Class(es)) field per report, appended to the front-end `<table>` element - e.g. `rwd-sticky-col` for `plg_system_fgresponsivetables`' sticky first column. Value is stripped down to letters, numbers, hyphens, underscores and spaces before output. New `table_css_class` column (`admin/sql/updates/{mysql,mssql}/1.4.3.sql`).

## 1.4.2
- Replaced the front-end pagination footer with a self-contained First/Prev/Next/Last implementation. The previous version used Joomla's `Pagination::getPagesLinks()`, whose default "First"/"Last" (and Prev/Next) icons come from icon-font classes (`icon-angle-double-left` etc.) that belong to the admin template and often aren't loaded on the site's front-end template - so those buttons existed in the DOM but rendered empty/invisible. The new pagination is built directly in `site/tmpl/report/default.php` from the report's `total`/`limit`/`limitstart`, uses plain text labels (translatable, new `COM_FGREPORTS_PAGINATION_*` strings), and needs no icon font.

## 1.4.1
- Added a per-report "Stackovať na mobile" (Stack On Mobile) toggle (default: on). Controls whether the `responsiv` CSS class is applied to that report's front-end table, which is what `plg_system_fgresponsivetables` (if installed and published) uses to stack the table into cards on narrow screens - has no effect without that plugin. New `stack_mobile` column (`admin/sql/updates/{mysql,mssql}/1.4.1.sql`).

## 1.4.0
- Added front-end pagination for report results. New `page_limit` column on `#__fgreports_reports` (0 = use the global default) plus a new global "Rows Per Page (default)" option in Options → Execution. Since a report's SQL script is opaque (can't safely inject `OFFSET/FETCH` into arbitrary scripts), the full result set is still fetched/cached exactly as before - pagination slices it in PHP per page, using Joomla's own `Pagination` class for the page links, so a cached report still only hits MS SQL once per cache window regardless of how many pages are viewed.
  - Enabled schema-version tracking (`<update><schemas>`) in the manifest for the first time - added `admin/sql/updates/mysql/1.4.0.sql` and `admin/sql/updates/mssql/1.4.0.sql` to add the column on existing installs; fresh installs get it directly via the updated `install.sql` files.

## 1.3.0
- The MS SQL connection password is now encrypted at rest instead of being stored as plain text in `#__extensions`. Added `CryptoHelper` (libsodium `secretbox`, key derived from the site's own `$secret` in `configuration.php`, versioned `fgenc:v1:` prefix) and a custom `cryptpassword` form field (`admin/fields/cryptpassword.php`, registered via `addfieldpath` on `admin/config.xml`'s `<config>` root - confirmed against `Form::syncPaths()`, which scans the whole XML tree for `addfieldpath` attributes, not just `<fields>` elements). The password field now always renders empty (the stored ciphertext never appears in the page HTML); leaving it blank on save keeps the currently stored password unchanged, entering a new value encrypts and replaces it. `ConnectionHelper::getConnection()` decrypts the stored value before connecting. A password saved before this update (plain text) is read as-is on first use and gets encrypted the next time Options is saved. If the site secret ever changes, a previously saved password can no longer be decrypted and needs to be re-entered in Options.

## 1.2.0
- Added a report picker to the "Jeden report" (single Report) menu item type: `site/tmpl/report/default.xml` now has a `<fields name="request">` block with a core `type="sql"` dropdown listing published reports by title, so the admin can pick exactly which report a menu item points to (no more manual `id` in the URL). No PHP changes needed - `Site\View\Report\HtmlView` already read `id` from the request, which is exactly how Joomla merges a menu item's "request" fields.

## 1.1.1
- Fixed SQL scripts getting silently corrupted on save, causing `Incorrect syntax near ''` on the front end even though "Run Preview" in the admin editor showed the script working fine. Root cause: the admin AJAX preview reads the script directly from POST with `filter=raw`, but the real Save path ran the value through the `sql_script` form field's default filter, which strips sequences that look like HTML tags - including ordinary SQL comparisons like `WHERE date < '2024-01-01'`. Added `filter="raw"` to the `sql_script` field in `admin/forms/report.xml` so the exact text is preserved on save, matching what Preview already does.
  - **Existing reports saved before this update may still have a corrupted script stored in the database** - open each one, re-check/retype the SQL (especially any `<`/`>` comparisons), and Save again after updating.

## 1.1.0
- Fixed the component not appearing at all under Menus → New → Menu Item Type. Joomla only lists a site view as a selectable menu item type if its `tmpl` folder contains a `default.xml` metadata file (`<metadata><layout title="...">...`) - this is a separate mechanism from routing/dispatching and isn't auto-detected from the view classes. Added `site/tmpl/reports/default.xml` and `site/tmpl/report/default.xml`. Note: the "Jeden report" (single Report) menu type still has no report picker field, so it always needs an `id` appended to the URL manually - use "Zoznam reportov" for a normal menu link.

## 1.0.9
- Fixed `Cannot read properties of undefined (reading 'isValid')` thrown from core.js when clicking Save / Apply / Save & New (Cancel was unaffected, since it skips validation). Root cause: our form has `class="form-validate"`, and `Joomla.submitbutton()` checks `document.formvalidator.isValid(form)` before submitting for any button using `CoreButtonsTrait`'s `apply()/save()/save2new()` - but `document.formvalidator` is only created once the `form.validate` JS asset is loaded, which happens via `HTMLHelper::_('behavior.formvalidator')`. That call was missing from `admin/tmpl/report/edit.php`. Added it (confirmed the exact method name against `libraries/src/HTML/Helpers/Behavior.php` - it's `formvalidator`, not `formvalidate`).

## 1.0.8
- Fixed Save / Save & Close / Apply doing nothing on the report edit form (only Cancel worked). Root cause: the form's `id` was `report-form`, but Joomla's core `Joomla.submitform()` JS - which is what `ToolbarHelper::apply()/save()/save2new()`'s buttons call by default - looks up the form via `document.getElementById('adminForm')` when no form is explicitly passed. With no element having that id, the click silently failed in the browser console instead of submitting. Renamed the form's `id` to `adminForm` (kept `name="adminForm"`, unchanged) and updated the preview script's selector to match.

## 1.0.7
Proactive cross-check against Joomla core (`joomla-cms` 6.1-dev branch) before it caused a runtime error, rather than waiting for one:
- **Fixed a real fatal bug**: `site/src/Model/ReportModel::getItem()` was declared `protected`, but `Joomla\CMS\MVC\Model\ItemModelInterface` (implemented by the `ItemModel` base class) requires it to be `public`. This would have thrown "Access level must be public" the first time anyone opened a front-end report. Changed to `public`.
- Added `validate="rules"` and `filter="rules"` to the permissions field in `admin/config.xml` to exactly match the core convention (`administrator/components/com_content/config.xml`).
- Verified, with no changes needed: `AdminController`/`FormController`/`AdminModel` naming-convention auto-detection (`view_list`, `context`, `view_item`) against our namespace/class names; `MVCFactory::createTable()`'s class-name assembly against `ReportTable`; `FormModelInterface::getForm()` signature; `StatefulModelInterface::setState()/getState()` visibility; the `accesslevel`, `rules`, `radio` (`joomla.form.field.radio.switcher` layout), `note`, `user`, and `calendar` field types used in `report.xml`/`config.xml`; and the toolbar button API (`ToolbarHelper::*` static methods, `Toolbar::getInstance()->standardButton()`) used in both admin views.

## 1.0.6
- Fixed "Trying to access array offset on value of type null" warnings on Components → FG SQL Reports → Options, and the connection settings form not loading any fields at all. Root cause: `admin/config.xml` had `<form>` as its root element; Joomla's `com_config` loads component options via `Form::loadFile(..., '/config')`, an XPath that only matches a root element literally named `<config>`. With `<form>` as root the XPath matched nothing, so zero fields were ever loaded and `$this->form->getXml()->config` was `null` when `com_config`'s own toolbar code read `->inlinehelp`/`->help` off it. Changed the root element to `<config>` (confirmed against `administrator/components/com_content/config.xml`) and added an explicit `<inlinehelp button="hide" />` so that node always exists.

## 1.0.5
- Fixed admin submenu items ("Reports", "New Report") showing raw untranslated language keys (`COM_FGREPORTS_MENU_REPORTS`, `COM_FGREPORTS_MENU_NEW_REPORT`) the first time the component's menu is expanded, before ever opening the component itself - they'd redraw correctly only after that first click. Root cause: the admin sidebar renders submenu labels from the `.sys.ini` language file (loaded for every installed component at admin boot, before any component is actually dispatched), not from the main `.ini` (only loaded once the component itself runs). Both menu strings were only defined in the main `.ini`. Added them to `en-GB.com_fgreports.sys.ini` and `sk-SK.com_fgreports.sys.ini` as well. After updating, a hard refresh (or Joomla's own cache clear) may be needed once to pick up the corrected `.sys.ini`.

## 1.0.4
- Fixed `Cannot access protected property ...HtmlView::$filterForm` fatal error, thrown from Joomla's own `layouts/joomla/searchtools/default/bar.php` when rendering the search bar added in 1.0.3. That core layout reads `$view->filterForm` and `$view->activeFilters` directly from outside the view class, so both properties must be `public` (confirmed against `administrator/components/com_content/src/View/Articles/HtmlView.php`) - they were declared `protected` since 1.0.0. No other properties were needed by the searchtools sublayouts (`list.php`, `filters.php`).

## 1.0.3
- Fixed `searchtools::render not found` fatal error on the admin Reports list. `HTMLHelper::_('searchtools.render', ...)` is not the current J6 API for rendering the search/filter bar - confirmed against Joomla core's own `com_content` admin template. Replaced it with `LayoutHelper::render('joomla.searchtools.default', ['view' => $this])`, which is what core list views actually use. The `searchtools.sort` calls in the column headers were already correct and are unchanged.

## 1.0.2
- Fixed `Layout default not found` fatal error when opening any view (admin Reports list, admin Report edit form, front-end Reports list, front-end Report). Verified against Joomla core's own com_content source: for native namespaced components, Joomla's `HtmlView` resolves templates at `<component root>/tmpl/<lowercase view name>/<layout>.php` (e.g. `admin/tmpl/reports/default.php`), NOT inside `src/View/<Name>/tmpl/` as several tutorials suggest. Moved all four template files accordingly:
  - `admin/src/View/Reports/tmpl/default.php` → `admin/tmpl/reports/default.php`
  - `admin/src/View/Report/tmpl/edit.php` → `admin/tmpl/report/edit.php`
  - `site/src/View/Reports/tmpl/default.php` → `site/tmpl/reports/default.php`
  - `site/src/View/Report/tmpl/default.php` → `site/tmpl/report/default.php`
  - Added the `tmpl` folder to `com_fgreports.xml` under both the site and admin `<files>` blocks.

## 1.0.1
- Fixed `Call to undefined method Joomla\CMS\Extension\MVCComponent::setRouterFactory()` fatal error on opening the component in the admin. The plain `MVCComponent` class does not implement `RouterServiceInterface`/`RouterServiceTrait`, so it has no `setRouterFactory()` method. Removed the `RouterFactory` service provider registration and the `setRouterFactory()` call from `services/provider.php` - the component doesn't need a custom SEF router for its current feature set (front-end works via standard `option=com_fgreports&view=...` requests).

## 1.0.0
- Initial release: MS SQL Server connection settings (Options), report manager (one SQL script = one report) with a live "Run Preview" in the admin editor, front-end report list and single-report views with per-report Joomla access levels and optional result caching.
