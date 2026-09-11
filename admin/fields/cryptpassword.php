<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use FG\Component\Fgreports\Administrator\Helper\CryptoHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\PasswordField;
use Joomla\Registry\Registry;

/**
 * Field type="cryptpassword". Renders as an always-empty password input
 * (the stored value is encrypted and must never appear in the page HTML),
 * and encrypts on submit. Leaving it blank on save keeps whatever password
 * is already stored.
 */
class JFormFieldCryptpassword extends PasswordField
{
    protected $type = 'Cryptpassword';

    protected function getInput()
    {
        // Never render the stored (encrypted) value back into the form.
        $this->value = '';

        return parent::getInput();
    }

    public function filter($value, $group = null, ?Registry $input = null)
    {
        $value = trim((string) $value);

        if ($value === '') {
            // Keep the currently saved (already encrypted) password unchanged.
            return (string) ComponentHelper::getParams('com_fgreports')->get($this->fieldname, '');
        }

        return CryptoHelper::encrypt($value);
    }
}
