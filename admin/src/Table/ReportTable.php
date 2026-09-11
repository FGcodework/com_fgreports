<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ReportTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fgreports_reports', 'id', $db);
    }

    public function check()
    {
        if (trim($this->title) === '') {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_FGREPORTS_ERROR_TITLE_REQUIRED'));

            return false;
        }

        if (trim($this->sql_script) === '') {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_FGREPORTS_ERROR_SQL_REQUIRED'));

            return false;
        }

        if (trim($this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->title);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = (string) time();
        }

        $this->alias = $this->getUniqueAlias($this->alias);

        $notableKeyword = \FG\Component\Fgreports\Administrator\Helper\ScriptGuard::findBlockedKeyword($this->sql_script);

        if ($notableKeyword !== null) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(
                \Joomla\CMS\Language\Text::sprintf('COM_FGREPORTS_WARNING_NOTABLE_KEYWORD', $notableKeyword),
                'warning'
            );
        }

        return true;
    }

    /**
     * Appends -2, -3, ... to the alias until it doesn't collide with another
     * report's alias. Frontend lookups (menu items without an explicit id,
     * or a future alias-based router) rely on the alias uniquely
     * identifying one report - without this, a duplicate alias means
     * whichever row the database happens to return first "wins",
     * unpredictably.
     */
    private function getUniqueAlias(string $alias): string
    {
        $db = $this->getDatabase();
        $candidate = $alias;
        $suffix = 1;

        while (true) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($this->getTableName()))
                ->where($db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $candidate);

            if (!empty($this->id)) {
                $query->where($db->quoteName('id') . ' != :id')
                    ->bind(':id', $this->id, \Joomla\Database\ParameterType::INTEGER);
            }

            $db->setQuery($query);

            if ((int) $db->loadResult() === 0) {
                return $candidate;
            }

            $suffix++;
            $candidate = $alias . '-' . $suffix;
        }
    }
}
