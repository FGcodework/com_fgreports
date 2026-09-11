<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Site\Model;

defined('_JEXEC') or die;

use FG\Component\Fgreports\Administrator\Helper\ConnectionHelper;
use Exception;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

class ReportModel extends ItemModel
{
    /**
     * Result: ['title', 'description', 'columns', 'rows' (current page only),
     * 'error', 'total', 'limit', 'limitstart', 'sort_column', 'sort_dir',
     * 'truncated']
     */
    public function getReportData(): array
    {
        $item = $this->getItem();

        if (!$item) {
            return ['error' => Text::_('COM_FGREPORTS_ERROR_REPORT_NOT_FOUND')];
        }

        $limit = (int) $item->page_limit > 0
            ? (int) $item->page_limit
            : (int) ComponentHelper::getParams('com_fgreports')->get('frontend_page_limit', 50);
        $limitstart = Factory::getApplication()->getInput()->getUint('limitstart', 0);
        $sortColumn = Factory::getApplication()->getInput()->getString('sort', '');
        $sortDir = strtolower(Factory::getApplication()->getInput()->getString('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $result = [
            'title'           => $item->title,
            'description'     => $item->description,
            'columns'         => [],
            'rows'            => [],
            'error'           => null,
            'total'           => 0,
            'limit'           => $limit,
            'limitstart'      => $limitstart,
            'stack_mobile'    => (bool) $item->stack_mobile,
            'table_css_class' => $this->sanitizeCssClasses((string) $item->table_css_class),
            'sort_column'     => '',
            'sort_dir'        => $sortDir,
            'truncated'       => false,
        ];

        try {
            $capped = $this->runWithCache($item);
            $allRows = $capped['rows'];
            $columns = $allRows ? array_keys(reset($allRows)) : [];

            // Only sort by a column that actually exists in this result set -
            // the value comes straight from the query string, so it must be
            // whitelisted against the real columns, never used as-is.
            if ($sortColumn !== '' && \in_array($sortColumn, $columns, true)) {
                $this->sortRows($allRows, $sortColumn, $sortDir);
                $result['sort_column'] = $sortColumn;
            }

            $total = \count($allRows);

            if ($limitstart >= $total && $total > 0) {
                $limitstart = $limit > 0 ? (int) (floor(($total - 1) / $limit) * $limit) : 0;
            }

            $pageRows = $limit > 0 ? \array_slice($allRows, $limitstart, $limit) : $allRows;

            $result['rows'] = $pageRows;
            $result['columns'] = $columns;
            $result['total'] = $total;
            $result['limitstart'] = $limitstart;
            $result['truncated'] = $capped['truncated'];
        } catch (Exception $e) {
            Log::add(
                sprintf('com_fgreports report #%d execution failed: %s', $item->id, $e->getMessage()),
                Log::ERROR,
                'com_fgreports'
            );

            $user = Factory::getApplication()->getIdentity();
            $result['error'] = $user->authorise('core.admin', 'com_fgreports')
                ? $e->getMessage()
                : Text::_('COM_FGREPORTS_ERROR_REPORT_EXECUTION_GENERIC');
        }

        return $result;
    }

    public function getItem($pk = null)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $user = Factory::getApplication()->getIdentity();

        $id = $pk ?? (int) $this->getState('report.id');
        $alias = (string) $this->getState('report.alias', '');

        $query->select('*')->from($db->quoteName('#__fgreports_reports'))
            ->where($db->quoteName('published') . ' = 1');

        if ($id > 0) {
            $query->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
        } elseif ($alias !== '') {
            $query->where($db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $alias);
        } else {
            return null;
        }

        $db->setQuery($query);
        $item = $db->loadObject();

        if (!$item) {
            return null;
        }

        if (!\in_array((int) $item->access, $user->getAuthorisedViewLevels(), true)) {
            return null;
        }

        return $item;
    }

    private function sortRows(array &$rows, string $column, string $dir): void
    {
        usort($rows, function (array $a, array $b) use ($column, $dir) {
            $valA = $a[$column] ?? null;
            $valB = $b[$column] ?? null;

            // Nulls always sort last, regardless of direction.
            if ($valA === null && $valB === null) {
                return 0;
            }

            if ($valA === null) {
                return 1;
            }

            if ($valB === null) {
                return -1;
            }

            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = $valA <=> $valB;
            } else {
                $cmp = strnatcasecmp((string) $valA, (string) $valB);
            }

            return $dir === 'desc' ? -$cmp : $cmp;
        });
    }

    private function sanitizeCssClasses(string $classes): string
    {
        $classes = preg_replace('/[^A-Za-z0-9_\- ]/', '', $classes) ?? '';

        return trim(preg_replace('/\s+/', ' ', $classes) ?? '');
    }

    private function runWithCache(object $item): array
    {
        $maxRows = (int) ComponentHelper::getParams('com_fgreports')->get('max_rows', 10000);
        $ttl = (int) $item->cache_ttl;

        if ($ttl <= 0) {
            return ConnectionHelper::runScript($item->sql_script, $maxRows);
        }

        $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
            ->createCacheController('callback', ['defaultgroup' => 'com_fgreports']);
        $cache->setLifeTime($ttl);
        $cache->setCaching(true);

        $cacheId = 'report_' . (int) $item->id . '_'
            . hash('sha256', $item->sql_script . '|' . $item->modified . '|' . $maxRows);

        $result = $cache->get(
            [ConnectionHelper::class, 'runScript'],
            [$item->sql_script, $maxRows],
            $cacheId
        );

        return \is_array($result) && isset($result['rows'], $result['truncated'])
            ? $result
            : ['rows' => [], 'truncated' => false];
    }
}
