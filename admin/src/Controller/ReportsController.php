<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Controller;

defined('_JEXEC') or die;

use FG\Component\Fgreports\Administrator\Helper\ConnectionHelper;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

class ReportsController extends AdminController
{
    public function getModel($name = 'Report', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Tries to open a connection to the configured MS SQL Server using the
     * saved global options, without running any report script.
     */
    public function testconnection()
    {
        $this->checkToken();

        if (!Factory::getApplication()->getIdentity()->authorise('core.options', 'com_fgreports')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
        }

        try {
            ConnectionHelper::getConnection();
            $this->setMessage(Text::_('COM_FGREPORTS_TEST_CONNECTION_SUCCESS'));
        } catch (Exception $e) {
            $this->setMessage(
                Text::sprintf('COM_FGREPORTS_TEST_CONNECTION_FAILED', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_fgreports&view=reports', false));
    }
}
