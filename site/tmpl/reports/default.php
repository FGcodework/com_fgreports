<?php
/**
 * @package     COM_FGSQLREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \FG\Component\Fgsqlreports\Site\View\Reports\HtmlView $this */

$itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
$itemIdSuffix = $itemId > 0 ? '&Itemid=' . $itemId : '';
?>
<div class="fgsqlreports-list">
    <h1><?php echo Text::_('COM_FGSQLREPORTS_REPORTS'); ?></h1>

    <?php if (empty($this->items)) : ?>
        <p><?php echo Text::_('COM_FGSQLREPORTS_NO_ITEMS_FOUND'); ?></p>
    <?php else : ?>
        <ul class="fgsqlreports-list-items">
            <?php foreach ($this->items as $item) : ?>
                <li>
                    <a href="<?php echo Route::_('index.php?option=com_fgsqlreports&view=report&id=' . (int) $item->id . $itemIdSuffix); ?>">
                        <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <?php if (!empty($item->description)) : ?>
                        <p class="small text-muted"><?php echo htmlspecialchars($item->description, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
