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

        $notableKeyword = \FG\Component\Fgreports\Administrator\Helper\ScriptGuard::findBlockedKeyword($this->sql_script);

        if ($notableKeyword !== null) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(
                \Joomla\CMS\Language\Text::sprintf('COM_FGREPORTS_WARNING_NOTABLE_KEYWORD', $notableKeyword),
                'warning'
            );
        }

        return true;
    }
}
