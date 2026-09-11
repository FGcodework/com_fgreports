<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \FG\Component\Fgreports\Administrator\View\Reports\HtmlView $this */

$user      = Factory::getApplication()->getIdentity();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canEdit   = $user->authorise('core.edit', 'com_fgreports');
$canChange = $user->authorise('core.edit.state', 'com_fgreports');
$saveOrder = ($listOrder === 'a.ordering');

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_fgreports&task=reports.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

$this->getDocument()->getWebAssetManager()->useScript('table.columns');
?>
<form action="<?php echo Route::_('index.php?option=com_fgreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_FGREPORTS_NO_ITEMS_FOUND'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="reportList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_FGREPORTS_MANAGER_REPORTS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_FGREPORTS_FIELD_TITLE_LABEL', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell text-center">
                                    <?php echo Text::_('JFIELD_ACCESS_LABEL'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell text-center">
                                    <?php echo Text::_('COM_FGREPORTS_FIELD_CACHE_TTL_LABEL'); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
                            <?php foreach ($this->items as $i => $item) :
                                $editLink = Route::_('index.php?option=com_fgreports&task=report.edit&id=' . (int) $item->id);
                                $canCheckin = $user->authorise('core.manage', 'com_fgreports');
                            ?>
                                <tr data-draggable-group="1">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php $iconClass = ($canChange && $saveOrder) ? '' : ' inactive'; ?>
                                        <span class="sortable-handler<?php echo $iconClass; ?>">
                                            <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                        </span>
                                        <?php if ($canChange && $saveOrder) : ?>
                                            <input type="text" name="order[]" size="5" value="<?php echo (int) $item->ordering; ?>" class="width-20 text-area-order hidden">
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'reports.', $canChange, 'cb'); ?>
                                    </td>
                                    <th scope="row">
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo $editLink; ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'reports.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?php echo $this->escape($item->alias); ?></div>
                                    </th>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php echo $this->escape($item->access_level); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php echo (int) $item->cache_ttl; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
