<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\View\Report;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $form;
    protected $state;

    public function display($tpl = null)
    {
        $this->item  = $this->get('Item');
        $this->form  = $this->get('Form');
        $this->state = $this->get('State');

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $isNew = ((int) $this->item->id === 0);

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_FGREPORTS_MANAGER_REPORT_NEW' : 'COM_FGREPORTS_MANAGER_REPORT_EDIT'),
            'pencil-2 fgreports'
        );

        ToolbarHelper::apply('report.apply');
        ToolbarHelper::save('report.save');
        ToolbarHelper::save2new('report.save2new');

        if ($isNew) {
            ToolbarHelper::cancel('report.cancel');
        } else {
            ToolbarHelper::cancel('report.cancel', 'JTOOLBAR_CLOSE');
        }
    }
}
