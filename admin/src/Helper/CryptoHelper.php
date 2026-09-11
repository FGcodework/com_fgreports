<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace FG\Component\Fgreports\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;

/**
 * Encrypts/decrypts the MS SQL connection password stored in the component's
 * params (#__extensions), using libsodium with a key derived from Joomla's
 * own site secret (configuration.php $secret).
 *
 * Values are stored as "fgenc:v1:" + base64(nonce . ciphertext). A value
 * that does NOT start with that prefix is treated as a legacy plaintext
 * password saved before this was introduced, and is returned as-is - it
 * will be re-encrypted the next time Options is saved.
 */
class CryptoHelper
{
    private const PREFIX = 'fgenc:v1:';

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, self::deriveKey());

        return self::PREFIX . base64_encode($nonce . $cipher);
    }

    public static function decrypt(string $stored): string
    {
        if ($stored === '') {
            return '';
        }

        if (!str_starts_with($stored, self::PREFIX)) {
            // Legacy plaintext value from before encryption was added.
            return $stored;
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);

        if ($raw === false || \strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Stored database password is corrupted.');
        }

        $nonce  = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::deriveKey());

        if ($plain === false) {
            throw new RuntimeException(
                'Could not decrypt the saved database password (the site secret may have changed). '
                . 'Re-enter the password in Options.'
            );
        }

        return $plain;
    }

    private static function deriveKey(): string
    {
        $secret = (string) Factory::getApplication()->get('secret', '');

        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
