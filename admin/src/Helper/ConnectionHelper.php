<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Builds a PDO (sqlsrv) connection to the external MS SQL Server using the
 * component's global connection settings, and runs report SQL scripts
 * against it.
 *
 * The extension only ever issues the SQL script exactly as stored - there is
 * no query builder involved. The real security boundary is:
 *   1) only users with core.options / core.edit on this component can define
 *      or change report scripts (see access.xml), and
 *   2) the MS SQL login configured below should be a READ-ONLY account.
 * The keyword guard in Helper\ScriptGuard is a defense-in-depth safety net,
 * not a substitute for #1 and #2.
 */
class ConnectionHelper
{
    /**
     * Create a PDO connection to the configured MS SQL Server using the
     * global component options.
     *
     * @throws RuntimeException when required settings are missing or the
     *                           connection fails.
     */
    public static function getConnection(): PDO
    {
        /** @var Registry $params */
        $params = ComponentHelper::getParams('com_fgreports');

        $host       = trim((string) $params->get('dbhost', ''));
        $port       = (int) $params->get('dbport', 1433);
        $database   = trim((string) $params->get('dbname', ''));
        $user       = trim((string) $params->get('dbuser', ''));
        $pass       = CryptoHelper::decrypt((string) $params->get('dbpass', ''));
        $encrypt    = (int) $params->get('dbencrypt', 1) === 1 ? 'yes' : 'no';
        $trustCert  = (int) $params->get('dbtrustcert', 0) === 1 ? 'yes' : 'no';
        $timeout    = (int) $params->get('dbtimeout', 30);
        $readOnly   = (int) $params->get('dbapplicationintent', 0) === 1;

        if ($host === '' || $database === '' || $user === '') {
            throw new RuntimeException(
                Factory::getApplication()->getLanguage()->_('COM_FGREPORTS_ERROR_CONNECTION_NOT_CONFIGURED')
            );
        }

        if (!\extension_loaded('pdo_sqlsrv')) {
            throw new RuntimeException(
                Factory::getApplication()->getLanguage()->_('COM_FGREPORTS_ERROR_PDO_SQLSRV_MISSING')
            );
        }

        $dsn = sprintf(
            'sqlsrv:Server=%s,%d;Database=%s;Encrypt=%s;TrustServerCertificate=%s;LoginTimeout=%d',
            $host,
            $port,
            $database,
            $encrypt,
            $trustCert,
            $timeout
        );

        if ($readOnly) {
            // Signals read-only intent to SQL Server - on an Availability
            // Group listener this can route the connection to a readable
            // secondary. On a standalone server (no AG) it's simply a no-op,
            // not an error. Opt-in (not default) since we can't verify the
            // target server's topology from here.
            $dsn .= ';ApplicationIntent=ReadOnly';
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // LoginTimeout above only bounds how long it takes to *connect* -
            // this bounds how long an actual query (the report's SQL script)
            // is allowed to run once connected, so a locked/slow report can't
            // hold a PHP request open indefinitely.
            $pdo->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, (int) $params->get('dbquerytimeout', 30));

            // PDO_SQLSRV does not accept "CharacterSet" as a DSN option (that's
            // only valid for the older function-based sqlsrv_connect() API) -
            // encoding is set via this attribute instead. UTF-8 is already the
            // PDO_SQLSRV default, but setting it explicitly avoids depending on
            // that default and protects against mojibake with diacritics
            // (ľščťžýáíé) when the driver/server locale assumptions differ.
            $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } catch (PDOException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $pdo;
    }

    /**
     * Run a report SQL script and return up to $rowLimit rows, plus whether
     * the result was actually cut off (i.e. the query had more rows than
     * that). Only the first result set is returned.
     *
     * $rowLimit is always enforced in PHP - it does not (and cannot, given
     * the script is opaque) inject a TOP/OFFSET-FETCH into the SQL itself.
     * SQL Server will still execute the full query server-side; this only
     * bounds how much of the result PHP actually buffers in memory, by
     * fetching one row past the limit (to detect truncation) and then
     * closing the cursor instead of reading the rest.
     *
     * @param   string  $sqlScript  The raw SQL script stored on the report.
     * @param   int     $rowLimit   Maximum number of rows to return.
     *
     * @return  array{rows: array, truncated: bool}
     */
    public static function runScript(string $sqlScript, int $rowLimit): array
    {
        $pdo = self::getConnection();

        $statement = $pdo->query($sqlScript);

        $rows = [];
        $count = 0;
        $truncated = false;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $count++;

            if ($count > $rowLimit) {
                $truncated = true;
                break;
            }

            $rows[] = $row;
        }

        $statement->closeCursor();

        return ['rows' => $rows, 'truncated' => $truncated];
    }
}
