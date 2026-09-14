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
use Joomla\CMS\Router\Route;

/** @var \FG\Component\Fgreports\Administrator\View\Report\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
$ajaxUrl = Route::_('index.php?option=com_fgreports&task=report.preview&format=raw', false);
?>
<form action="<?php echo Route::_('index.php?option=com_fgreports&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" name="adminForm" id="adminForm" class="form-validate" aria-label="<?php echo Text::_('COM_FGREPORTS_MANAGER_REPORT_EDIT'); ?>">

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('title'); ?>
                    <?php echo $this->form->renderField('alias'); ?>
                    <?php echo $this->form->renderField('description'); ?>
                    <?php echo $this->form->renderField('sql_script'); ?>

                    <?php if (Factory::getApplication()->getIdentity()->authorise('fgreports.execute', 'com_fgreports')) : ?>
                        <p>
                            <button type="button" class="btn btn-secondary" id="fgreports-preview-btn">
                                <span class="icon-play" aria-hidden="true"></span>
                                <?php echo Text::_('COM_FGREPORTS_BUTTON_PREVIEW'); ?>
                            </button>
                            <span id="fgreports-preview-status" class="ms-2"></span>
                        </p>

                        <div id="fgreports-preview-result" class="table-responsive"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('cache_ttl'); ?>
                    <?php echo $this->form->renderField('page_limit'); ?>
                    <?php echo $this->form->renderField('stack_mobile'); ?>
                    <?php echo $this->form->renderField('table_css_class'); ?>
                    <?php echo $this->form->renderField('enable_sorting'); ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script id="fgreports-preview-config" type="application/json">
<?php echo json_encode([
    'ajaxUrl'      => $ajaxUrl,
    'sqlFieldName' => 'jform[sql_script]',
    'labels'       => [
        'running'   => Text::_('COM_FGREPORTS_PREVIEW_RUNNING'),
        'noRows'    => Text::_('COM_FGREPORTS_PREVIEW_NO_ROWS'),
        'rowCount'  => Text::_('COM_FGREPORTS_PREVIEW_ROW_COUNT'),
        'truncated' => Text::_('COM_FGREPORTS_PREVIEW_TRUNCATED'),
    ],
]); ?>
</script>
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('fgreports-preview-btn');
        var status = document.getElementById('fgreports-preview-status');
        var resultBox = document.getElementById('fgreports-preview-result');
        var config = JSON.parse(document.getElementById('fgreports-preview-config').textContent);

        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            var sqlField = document.querySelector('[name="' + config.sqlFieldName + '"]');

            var body = new URLSearchParams();
            body.set('sql_script', sqlField ? sqlField.value : '');

            // Include the Joomla CSRF token hidden field(s) present in the form.
            document.querySelectorAll('#adminForm input[type="hidden"]').forEach(function (input) {
                if (input.name && input.name !== 'task') {
                    body.set(input.name, input.value);
                }
            });

            btn.disabled = true;
            status.textContent = config.labels.running;
            resultBox.innerHTML = '';

            fetch(config.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btn.disabled = false;
                    status.textContent = '';
                    resultBox.innerHTML = '';

                    if (!data.success) {
                        var errBox = document.createElement('div');
                        errBox.className = 'alert alert-danger';
                        errBox.textContent = data.message;
                        resultBox.appendChild(errBox);
                        return;
                    }

                    if (data.message) {
                        var warnBox = document.createElement('div');
                        warnBox.className = 'alert alert-warning';
                        warnBox.textContent = data.message;
                        resultBox.appendChild(warnBox);
                    }

                    if (!data.rows || data.rows.length === 0) {
                        var infoBox = document.createElement('div');
                        infoBox.className = 'alert alert-info';
                        infoBox.textContent = config.labels.noRows;
                        resultBox.appendChild(infoBox);
                        return;
                    }

                    var columns = Object.keys(data.rows[0]);
                    var table = document.createElement('table');
                    table.className = 'table table-striped table-sm';

                    var thead = table.createTHead();
                    var headRow = thead.insertRow();
                    columns.forEach(function (col) {
                        var th = document.createElement('th');
                        th.textContent = col;
                        headRow.appendChild(th);
                    });

                    var tbody = table.createTBody();
                    data.rows.forEach(function (row) {
                        var tr = tbody.insertRow();
                        columns.forEach(function (col) {
                            var td = tr.insertCell();
                            var value = row[col];
                            td.textContent = (value === null || value === undefined) ? '' : String(value);
                        });
                    });

                    resultBox.appendChild(table);

                    var caption = document.createElement('p');
                    caption.className = 'text-muted small';
                    caption.textContent = data.rows.length + ' ' + config.labels.rowCount
                        + (data.truncated ? ' - ' + config.labels.truncated : '');
                    resultBox.appendChild(caption);
                })
                .catch(function (err) {
                    btn.disabled = false;
                    status.textContent = '';
                    resultBox.innerHTML = '<div class="alert alert-danger"></div>';
                    resultBox.querySelector('.alert').textContent = String(err);
                });
        });
    });
})();
</script>
