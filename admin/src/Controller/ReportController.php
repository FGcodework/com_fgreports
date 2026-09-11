<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Controller;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;

class ReportController extends FormController
{
    /**
     * AJAX task: run the SQL script currently in the edit form (not
     * necessarily saved yet) and return a JSON preview. Requires
     * core.edit/core.create (same as editing the form) AND the dedicated
     * fgreports.execute action - the latter exists specifically because
     * "can create/edit reports" and "can interactively run arbitrary SQL
     * against the reporting database right now, without saving anything"
     * are different levels of trust and some sites may want to grant one
     * without the other.
     */
    public function preview()
    {
        $this->checkToken('request');

        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        $canEdit = $user->authorise('core.edit', 'com_fgreports') || $user->authorise('core.create', 'com_fgreports');

        if (!$canEdit || !$user->authorise('fgreports.execute', 'com_fgreports')) {
            $this->sendJson(false, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));

            return;
        }

        $sqlScript = $app->getInput()->post->get('sql_script', '', 'raw');

        if (trim($sqlScript) === '') {
            $this->sendJson(false, Text::_('COM_FGREPORTS_ERROR_SQL_REQUIRED'));

            return;
        }

        $notableKeyword = \FG\Component\Fgreports\Administrator\Helper\ScriptGuard::findBlockedKeyword($sqlScript);
        $warning = $notableKeyword !== null
            ? Text::sprintf('COM_FGREPORTS_WARNING_NOTABLE_KEYWORD', $notableKeyword)
            : '';

        /** @var \FG\Component\Fgreports\Administrator\Model\ReportModel $model */
        $model = $this->getModel('Report', 'Administrator', ['ignore_request' => true]);

        try {
            $preview = $model->preview($sqlScript);
            $this->sendJson(true, $warning, $preview['rows'], $preview['truncated']);
        } catch (Exception $e) {
            $this->sendJson(false, $e->getMessage());
        }
    }

    private function sendJson(bool $success, string $message = '', array $data = [], bool $truncated = false): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();

        echo json_encode([
            'success'   => $success,
            'message'   => $message,
            'rows'      => $data,
            'truncated' => $truncated,
        ]);

        $app->close();
    }
}
