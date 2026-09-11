<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Site\View\Reports;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $items;

    public function display($tpl = null)
    {
        /** @var \FG\Component\Fgreports\Site\Model\ReportsModel $model */
        $model = $this->getModel();

        $this->items = $model->getItems();

        return parent::display($tpl);
    }
}
