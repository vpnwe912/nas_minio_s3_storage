<?php
/** @var array $stats */

use yii\helpers\Html;
?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= Html::encode($stats['summary']['buckets'] ?? '-') ?></h3>
                <p>Бакеты</p>
            </div>
            <div class="icon"><i class="fas fa-database"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= Html::encode($stats['summary']['objects'] ?? '-') ?></h3>
                <p>Объекты</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= Html::encode($stats['summary']['used'] ?? '-') ?></h3>
                <p>Использовано</p>
            </div>
            <div class="icon"><i class="fas fa-hdd"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= Html::encode($stats['uptime'] ?? '-') ?></h3>
                <p>Аптайм</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Информация о сервере MinIO</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tr>
                <th>Endpoint</th>
                <td>
                    <?php if (($stats['status'] ?? '') == '●'): ?>
                        <i class="fas fa-check-circle text-success" title="Online"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle text-danger" title="Offline"></i>
                    <?php endif; ?>
                    <?= Html::encode($stats['endpoint'] ?? '-') ?>
                </td>
            </tr>
            <tr>
                <th>Версия</th>
                <td><?= Html::encode($stats['version'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Network</th>
                <td><?= Html::encode($stats['network'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Drives</th>
                <td><?= Html::encode($stats['drives'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Pool</th>
                <td><?= Html::encode($stats['pool'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Драйвы (онлайн/оффлайн/EC)</th>
                <td><?= Html::encode("{$stats['drives_online']}/{$stats['drives_offline']} (EC: {$stats['ec']})") ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($stats['storage'])): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">Storage Usage</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <?php foreach ($stats['storage'] as $k => $v): ?>
                            <th><?= Html::encode($k) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($stats['storage'] as $k => $v): ?>
                            <td><?= Html::encode($v) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
