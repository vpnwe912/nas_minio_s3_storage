<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\services\MinioStatsService;

class DashboardController extends Controller
{
    public function actionIndex()
    {
        $stats = MinioStatsService::getServerInfo();
        return $this->render('index', ['stats' => $stats]);
    }
}
