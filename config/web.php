<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$cookieValidationKey = getenv('COOKIE_VALIDATION_KEY')
    ?: $_ENV['COOKIE_VALIDATION_KEY'] 
    ?: 'fallback';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'language' => 'en',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'components' => [
        'minio' => [
            'class'    => 'app\components\MinioService',
        ],
        'minioAdmin' => [
            'class'    => 'app\components\MinioAdminService',
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => $cookieValidationKey,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                ''                                => 'site/index',              // / → site/index
                '<controller:\w+>'               => '<controller>/index',      // /user → user/index
                '<controller:\w+>/<action:\w+>'  => '<controller>/<action>',   // /user/create → user/create
                // можно добавлять свои правила...

            ],
        ],

    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
