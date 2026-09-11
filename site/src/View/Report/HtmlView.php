<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Site\View\Report;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $report;

    public function display($tpl = null)
    {
        $app   = Factory::getApplication();
        $id    = $app->getInput()->getInt('id', 0);
        $alias = $app->getInput()->getString('alias', '');

        /** @var \FG\Component\Fgreports\Site\Model\ReportModel $model */
        $model = $this->getModel();
        $model->setState('report.id', $id);
        $model->setState('report.alias', $alias);

        $this->report = $model->getReportData();

        if (!empty($this->report['title'])) {
            $this->document->setTitle($this->report['title']);
        }

        return parent::display($tpl);
    }
}
