<?php
/**
 * @package     COM_FGREPORTS
 * @copyright   Copyright (C) Fero. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \FG\Component\Fgreports\Site\View\Report\HtmlView $this */

$report = $this->report;
?>
<div class="fgreports-report">
    <?php if (!empty($report['error'])) : ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($report['error'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else : ?>
        <h1><?php echo htmlspecialchars($report['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($report['description'])) : ?>
            <p class="fgreports-description"><?php echo htmlspecialchars($report['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($report['truncated'])) : ?>
            <div class="alert alert-warning">
                <?php echo Text::sprintf('COM_FGREPORTS_ROWS_TRUNCATED', (int) $report['total']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($report['rows'])) : ?>
            <div class="alert alert-info"><?php echo Text::_('COM_FGREPORTS_PREVIEW_NO_ROWS'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <?php
                $tableClass = 'table table-striped table-bordered fgreports-table';
                $tableClass .= !empty($report['stack_mobile']) ? ' responsiv' : '';
                $tableClass .= !empty($report['table_css_class']) ? ' ' . $report['table_css_class'] : '';
                ?>
                <table class="<?php echo $tableClass; ?>">
                    <thead>
                        <tr>
                            <?php foreach ($report['columns'] as $col) :
                                $isSorted = $report['sort_column'] === $col;
                                $nextDir  = ($isSorted && $report['sort_dir'] === 'asc') ? 'desc' : 'asc';

                                $sortUri = clone Uri::getInstance();
                                $sortUri->setVar('sort', $col);
                                $sortUri->setVar('sort_dir', $nextDir);
                                $sortUri->setVar('limitstart', 0);
                                $sortUrl = htmlspecialchars($sortUri->toString(), ENT_QUOTES, 'UTF-8');

                                $indicator = '';
                                if ($isSorted) {
                                    $indicator = $report['sort_dir'] === 'asc' ? " \u{25B2}" : " \u{25BC}";
                                }
                            ?>
                                <th scope="col" class="<?php echo $isSorted ? 'fgreports-sorted' : ''; ?>">
                                    <a href="<?php echo $sortUrl; ?>" class="fgreports-sort-link">
                                        <?php echo htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8'); ?><?php echo $indicator; ?>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['rows'] as $row) : ?>
                            <tr>
                                <?php foreach ($report['columns'] as $col) :
                                    $value = $row[$col] ?? '';
                                ?>
                                    <td data-label="<?php echo htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $total      = (int) $report['total'];
            $limit      = (int) $report['limit'];
            $limitstart = (int) $report['limitstart'];
            ?>
            <?php if ($limit > 0 && $total > $limit) :
                $totalPages  = (int) ceil($total / $limit);
                $currentPage = (int) floor($limitstart / $limit) + 1;
                $isFirst     = $currentPage <= 1;
                $isLast      = $currentPage >= $totalPages;

                $pageUrl = function (int $start) {
                    $uri = clone Uri::getInstance();
                    $uri->setVar('limitstart', max(0, $start));

                    return htmlspecialchars($uri->toString(), ENT_QUOTES, 'UTF-8');
                };

                $rangeStart = $total > 0 ? $limitstart + 1 : 0;
                $rangeEnd   = min($limitstart + $limit, $total);
                ?>
                <nav class="fgreports-pagination-nav d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2" aria-label="<?php echo Text::_('COM_FGREPORTS_PAGINATION_NAV_LABEL'); ?>">
                    <div class="fgreports-pagination-counter small text-muted">
                        <?php echo Text::sprintf('COM_FGREPORTS_PAGINATION_RANGE', $rangeStart, $rangeEnd, $total); ?>
                    </div>
                    <ul class="pagination mb-0">
                        <li class="page-item<?php echo $isFirst ? ' disabled' : ''; ?>">
                            <?php if ($isFirst) : ?>
                                <span class="page-link"><?php echo Text::_('COM_FGREPORTS_PAGINATION_FIRST'); ?></span>
                            <?php else : ?>
                                <a class="page-link" href="<?php echo $pageUrl(0); ?>"><?php echo Text::_('COM_FGREPORTS_PAGINATION_FIRST'); ?></a>
                            <?php endif; ?>
                        </li>
                        <li class="page-item<?php echo $isFirst ? ' disabled' : ''; ?>">
                            <?php if ($isFirst) : ?>
                                <span class="page-link"><?php echo Text::_('COM_FGREPORTS_PAGINATION_PREV'); ?></span>
                            <?php else : ?>
                                <a class="page-link" href="<?php echo $pageUrl($limitstart - $limit); ?>"><?php echo Text::_('COM_FGREPORTS_PAGINATION_PREV'); ?></a>
                            <?php endif; ?>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link"><?php echo Text::sprintf('COM_FGREPORTS_PAGINATION_PAGE_OF', $currentPage, $totalPages); ?></span>
                        </li>
                        <li class="page-item<?php echo $isLast ? ' disabled' : ''; ?>">
                            <?php if ($isLast) : ?>
                                <span class="page-link"><?php echo Text::_('COM_FGREPORTS_PAGINATION_NEXT'); ?></span>
                            <?php else : ?>
                                <a class="page-link" href="<?php echo $pageUrl($limitstart + $limit); ?>"><?php echo Text::_('COM_FGREPORTS_PAGINATION_NEXT'); ?></a>
                            <?php endif; ?>
                        </li>
                        <li class="page-item<?php echo $isLast ? ' disabled' : ''; ?>">
                            <?php if ($isLast) : ?>
                                <span class="page-link"><?php echo Text::_('COM_FGREPORTS_PAGINATION_LAST'); ?></span>
                            <?php else : ?>
                                <a class="page-link" href="<?php echo $pageUrl(($totalPages - 1) * $limit); ?>"><?php echo Text::_('COM_FGREPORTS_PAGINATION_LAST'); ?></a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
