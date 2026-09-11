<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Model;

defined('_JEXEC') or die;

use FG\Component\Fgreports\Administrator\Helper\ConnectionHelper;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class ReportModel extends AdminModel
{
    public $typeAlias = 'com_fgreports.report';

    public function getTable($type = 'Report', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_fgreports.edit.report.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_fgreports.report',
            'report',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item !== false && empty($item->id)) {
            $item->cache_ttl = (int) \Joomla\CMS\Component\ComponentHelper::getParams('com_fgreports')
                ->get('default_cache_ttl', 0);
        }

        return $item;
    }

    /**
     * AdminModel::save() calls this hook but its own base implementation is
     * an empty stub ("Derived class will provide its own implementation if
     * required.") - without overriding it, created/created_by/modified/
     * modified_by/ordering are never actually populated.
     */
    protected function prepareTable($table): void
    {
        $date   = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if (empty($table->id)) {
            $table->created    = $date;
            $table->created_by = $userId;
            $table->ordering   = $table->getNextOrder();
        }

        $table->modified    = $date;
        $table->modified_by = $userId;
    }

    /**
     * Execute a report's SQL script (or an arbitrary script passed for a
     * not-yet-saved report) and return a limited preview result set.
     *
     * @return array{rows: array, truncated: bool}
     * @throws Exception on connection or query failure - caller shows the message.
     */
    public function preview(string $sqlScript): array
    {
        $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_fgreports');
        $limit  = (int) $params->get('preview_row_limit', 100);

        return ConnectionHelper::runScript($sqlScript, $limit);
    }

    public function save($data)
    {
        $result = parent::save($data);

        if ($result) {
            // The front-end cache key already changes with the SQL/modified
            // content (see Site\Model\ReportModel::runWithCache()), but also
            // clear the whole group here so old, now-unreachable cache
            // entries don't just sit on disk until they naturally expire.
            try {
                Factory::getContainer()->get(\Joomla\CMS\Cache\CacheControllerFactoryInterface::class)
                    ->createCacheController('callback', ['defaultgroup' => 'com_fgreports'])
                    ->clean('com_fgreports');
            } catch (Exception $e) {
                // Non-fatal - the content-hash cache key already prevents
                // stale results from being served either way.
            }
        }

        return $result;
    }
}
