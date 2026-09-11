<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Classic (non-namespaced, duck-typed) installer script class - deliberately
 * not implementing Joomla\CMS\Installer\InstallerScriptInterface, since that
 * interface's availability/behaviour has been less reliably consistent
 * across Joomla versions in our experience than the plain method-name
 * convention Joomla has supported unchanged since 3.x.
 */
class Com_fgreportsInstallerScript
{
    /**
     * Non-blocking check for the pdo_sqlsrv extension before install/update.
     * The component still installs fine without it - it's just useless
     * until pdo_sqlsrv is available - so this only warns, never fails.
     */
    public function preflight($type, $parent)
    {
        if (!\extension_loaded('pdo_sqlsrv')) {
            // Literal text, not Text::_() - preflight runs before this
            // component's own language files exist on disk yet, so a
            // language key would just show up untranslated.
            Factory::getApplication()->enqueueMessage(
                'FG SQL Reports: the PHP pdo_sqlsrv extension is not installed/enabled. '
                . 'The component will install fine, but reports won\'t run until you install '
                . 'the Microsoft Drivers for PHP for SQL Server and enable pdo_sqlsrv.',
                'warning'
            );
        }

        return true;
    }

    /**
     * On update, re-encrypts a legacy plaintext MS SQL connection password
     * left over from before password encryption was introduced (1.3.0).
     * Never blocks the update - any failure here is swallowed and simply
     * leaves the password as it was, to be picked up again on the next
     * update.
     */
    public function postflight($type, $parent)
    {
        if ($type !== 'update') {
            return true;
        }

        try {
            $this->reencryptLegacyPassword();
        } catch (\Throwable $e) {
            // Never let a migration helper break the update itself.
        }

        return true;
    }

    private function reencryptLegacyPassword(): void
    {
        if (!class_exists(\FG\Component\Fgreports\Administrator\Helper\CryptoHelper::class)) {
            return;
        }

        /** @var Table $table */
        $table = Table::getInstance('extension');

        if (!$table->load(['type' => 'component', 'element' => 'com_fgreports'])) {
            return;
        }

        $params = json_decode((string) $table->params, true) ?: [];
        $current = (string) ($params['dbpass'] ?? '');

        if ($current === '' || str_starts_with($current, 'fgenc:v1:')) {
            // Nothing to migrate - empty, or already encrypted.
            return;
        }

        $params['dbpass'] = \FG\Component\Fgreports\Administrator\Helper\CryptoHelper::encrypt($current);
        $table->params = json_encode($params);
        $table->store();
    }
}
