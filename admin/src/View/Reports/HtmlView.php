<?php
/**
 * @package     COM_FGSQLREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgsqlreports\Administrator\View\Reports;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        /** @var \FG\Component\Fgsqlreports\Administrator\Model\ReportsModel $model */
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination     = $model->getPagination();
        $this->state          = $model->getState();
        $this->filterForm     = $model->getFilterForm();
        $this->activeFilters  = $model->getActiveFilters();

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_FGSQLREPORTS_MANAGER_REPORTS'), 'list fgsqlreports');

        if ($user->authorise('core.create', 'com_fgsqlreports')) {
            ToolbarHelper::addNew('report.add');
        }

        if ($user->authorise('core.edit.state', 'com_fgsqlreports') && !empty($this->items)) {
            ToolbarHelper::publish('reports.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('reports.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($user->authorise('core.delete', 'com_fgsqlreports') && !empty($this->items)) {
            ToolbarHelper::deleteList('', 'reports.delete', 'JTOOLBAR_DELETE');
        }

        if ($user->authorise('core.options', 'com_fgsqlreports')) {
            $toolbar = Toolbar::getInstance();
            $toolbar->standardButton('test-connection', 'COM_FGSQLREPORTS_TOOLBAR_TEST_CONNECTION', 'reports.testconnection')
                ->icon('icon-connection');

            ToolbarHelper::preferences('com_fgsqlreports');
        }
    }
}
