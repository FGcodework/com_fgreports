<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Helper;

defined('_JEXEC') or die;

/**
 * A best-effort, purely advisory keyword scan for report scripts. It is NOT
 * a SQL parser, it is NOT a security boundary, and it never blocks
 * anything - it only prompts a heads-up message for a human to double-check.
 *
 * It has both false positives (e.g. SELECT 'UPDATE' AS Operation, or the
 * word inside a comment) and false negatives (e.g. SELECT ... INTO a new
 * table, or any statement using a keyword not in this list). Do not treat
 * "no keyword found" as "this script is safe".
 *
 * The actual, real security boundary for report scripts is:
 *   1) only users with core.edit/core.create on this component can write or
 *      change a report's SQL at all (see access.xml), and
 *   2) the MS SQL login configured in Options should have the minimum
 *      privilege that still works - ideally plain SELECT on specific
 *      reporting views, not membership in a broad role like db_datareader
 *      if the reporting user isn't meant to see every table in the
 *      database.
 */
class ScriptGuard
{
    /**
     * Keywords that a normal read-only reporting SELECT has no reason to
     * contain. Flagging one of these is a prompt to double-check the
     * script and the DB login's actual permissions - it is not proof of
     * anything either way.
     *
     * @var string[]
     */
    private const NOTABLE_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
        'CREATE', 'EXEC', 'EXECUTE', 'MERGE', 'GRANT', 'REVOKE', 'DENY',
        'BACKUP', 'DBCC', 'WAITFOR', 'KILL', 'INTO',
        'sp_executesql', 'xp_cmdshell',
    ];

    /**
     * Returns the first notable keyword found as a whole word, or null if
     * none matched. A null result does not mean the script is safe.
     */
    public static function findBlockedKeyword(string $sqlScript): ?string
    {
        foreach (self::NOTABLE_KEYWORDS as $keyword) {
            if (preg_match('/(?<![\w])' . preg_quote($keyword, '/') . '(?![\w])/i', $sqlScript)) {
                return $keyword;
            }
        }

        return null;
    }
}
