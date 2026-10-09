<?php
/**
 * @package     COM_FGSQLREPORTS
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
class Com_fgsqlreportsInstallerScript
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
        try {
            if ($type === 'update') {
                $this->reencryptLegacyPassword();
            } elseif ($type === 'install') {
                $this->migrateFromLegacyComponent();
            }
        } catch (\Throwable $e) {
            // Never let a migration helper break the install/update itself.
        }

        return true;
    }

    /**
     * One-time migration from the pre-2.0.0 name of this component
     * (com_fgreports, table #__fgreports_reports). A fresh install of
     * com_fgsqlreports copies the old reports and the old Options (including
     * the already-encrypted connection password) across - but only if the new
     * table is still empty, and it never touches or deletes the old data.
     * Menu items and ACL rules of the old component are NOT migrated.
     */
    private function migrateFromLegacyComponent(): void
    {
        $db     = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $tables = array_map('strtolower', $db->getTableList());
        $old    = strtolower($db->getPrefix() . 'fgreports_reports');
        $new    = strtolower($db->getPrefix() . 'fgsqlreports_reports');

        if (!\in_array($old, $tables, true) || !\in_array($new, $tables, true)) {
            return;
        }

        $count = (int) $db->setQuery(
            $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__fgsqlreports_reports'))
        )->loadResult();

        if ($count > 0) {
            return;
        }

        $db->setQuery(
            'INSERT INTO ' . $db->quoteName('#__fgsqlreports_reports')
            . ' SELECT * FROM ' . $db->quoteName('#__fgreports_reports')
        )->execute();

        // Options (connection settings etc.) of the old component.
        $oldParams = (string) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_fgreports'))
        )->loadResult();

        if ($oldParams !== '' && $oldParams !== '{}') {
            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('params') . ' = ' . $db->quote($oldParams))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('com_fgsqlreports'))
            )->execute();
        }

        Factory::getApplication()->enqueueMessage(
            'FG SQL Reports: reports and Options were copied from the old com_fgreports component. '
            . 'You can now uninstall com_fgreports and re-create its menu items using the new component.',
            'message'
        );
    }

    private function reencryptLegacyPassword(): void
    {
        if (!class_exists(\FG\Component\Fgsqlreports\Administrator\Helper\CryptoHelper::class)) {
            return;
        }

        /** @var Table $table */
        $table = Table::getInstance('extension');

        if (!$table->load(['type' => 'component', 'element' => 'com_fgsqlreports'])) {
            return;
        }

        $params = json_decode((string) $table->params, true) ?: [];
        $current = (string) ($params['dbpass'] ?? '');

        if ($current === '' || str_starts_with($current, 'fgenc:v1:')) {
            // Nothing to migrate - empty, or already encrypted.
            return;
        }

        $params['dbpass'] = \FG\Component\Fgsqlreports\Administrator\Helper\CryptoHelper::encrypt($current);
        $table->params = json_encode($params);
        $table->store();
    }
}
