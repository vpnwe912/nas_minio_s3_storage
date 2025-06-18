<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Binaries files';
?>
<div class=" py-4">
    <!-- <h2><?= Html::encode($this->title) ?></h2> -->
    <?php foreach (Yii::$app->session->getAllFlashes() as $key => $msg): ?>
        <div class="alert alert-<?= $key ?>"><?= $msg ?></div>
    <?php endforeach; ?>

    <div class="mb-3">
        <a href="<?= Url::to(['binaries/create']) ?>" class="btn btn-success">Upload new file</a>
    </div>

    <div class="card">
        <div class="card-header">List of binaries</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>File</th>
                        <th>Version</th>
                        <th>Size</th>
                        <th>SHA256</th>
                        <th>Date</th>
                        <th>Download</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($binaries as $bin): ?>
                    <tr>
                        <td><?= Html::encode($bin->name) ?></td>
                        <td><?= Html::encode($bin->filename) ?></td>
                        <td><?= Html::encode($bin->version) ?></td>
                        <td><?= Yii::$app->formatter->asShortSize($bin->size) ?></td>
                        <td style="font-size:12px; max-width:120px; word-break:break-all;"><?= Html::encode($bin->hash) ?></td>
                        <td><?= $bin->updated_at ?></td>
                        <td>
                            <a href="<?= Url::to(['/api/download/'.$bin->filename]) ?>" class="btn btn-primary btn-sm" target="_blank">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </td>
                        <td>
                            <a href="<?= Url::to(['binaries/update', 'id'=>$bin->id]) ?>" class="btn btn-warning btn-sm">Update</a>
                            <a href="<?= Url::to(['binaries/delete', 'id'=>$bin->id]) ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this file?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
