<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class ReportsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'title', 'ordering'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);

        // This list has no pagination UI and is documented ("Zoznam
        // reportov") as showing every published report the user may see -
        // without this, it would silently inherit the site's global
        // Global Configuration "List Limit" (commonly 20) and cut off the
        // list once there are more reports than that.
        $this->setState('list.limit', 0);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $user = Factory::getApplication()->getIdentity();

        $query->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.description']))
            ->from($db->quoteName('#__fgreports_reports', 'a'))
            ->where($db->quoteName('a.published') . ' = 1');

        $levels = $user->getAuthorisedViewLevels();
        $query->whereIn($db->quoteName('a.access'), $levels);

        $query->order($db->quoteName('a.ordering') . ' ASC, ' . $db->quoteName('a.title') . ' ASC');

        return $query;
    }
}
