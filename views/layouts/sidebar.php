<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/" class="brand-link">
        <img src="<?=$assetDir?>/img/AdminLTELogo.png" alt="MinIO S3 Storage" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><?= Yii::$app->params['projectName'] ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?=$assetDir?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="/site/index" class="d-block"><?= Yii::$app->user->identity->username ?></a>
            </div>
        </div>


        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <?php
            echo \hail812\adminlte\widgets\Menu::widget([
                'options' => [
                    'class' => 'nav nav-pills nav-sidebar flex-column nav-legacy', // ← ВАЖНО!
                    'data-widget' => 'treeview',
                    'role' => 'menu',
                    'data-accordion' => 'false'
                ],

                'items' => [
                    ['label' => 'Dashboard', 'url' => ['site/index'], 'icon' => 'tachometer-alt'],
                    [
                        'label' => 'MinIO',
                        'icon' => 'cloud',
                        'badge' => '',
                        'items' => [
                            [
                                'label' => 'Bucket', 
                                'url' => ['bucket/index'], 
                                'active' =>
                                    Yii::$app->controller->id === 'bucket'
                                    || (Yii::$app->controller->id === 'object' && in_array(Yii::$app->controller->action->id, ['browse', 'upload', 'create', 'delete'])),
                                'icon' => ''
                            ],
                            [
                                'label' => 'Groups', 
                                'url' => ['minio-group/index'],
                                'active' =>
                                    Yii::$app->controller->id === 'minio-group'
                                    || (Yii::$app->controller->id === 'object' && in_array(Yii::$app->controller->action->id, ['browse', 'upload', 'create', 'delete'])),
                                'icon' => ''
                            ],

                            [
                                'label' => 'Policy', 
                                'url' => ['minio-policy/index'], 
                                'active' =>
                                    Yii::$app->controller->id === 'minio-policy'
                                    || (Yii::$app->controller->id === 'object' && in_array(Yii::$app->controller->action->id, ['browse', 'upload', 'create', 'delete'])),
                                'icon' => ''
                            ],
                            [
                                'label' => 'Users', 
                                'url' => ['minio-user/index'], 
                                'active' =>
                                Yii::$app->controller->id === 'minio-user'
                                || (Yii::$app->controller->id === 'object' && in_array(Yii::$app->controller->action->id, ['browse', 'upload', 'create', 'delete'])),
                                'icon' => ''
                            ],
                        ]
                    ],

                    // [
                    //     'label' => 'Windiows Server',
                    //     'icon' => 'th',
                    //     'badge' => '',
                    //     'items' => [
                    //         ['label' => 'Dashboard', 'url' => ['minio-group/index2'], 'icon' => ''],
                    //         ['label' => 'Services', 'url' => ['minio-policy/index2'], 'icon' => ''],
                    //         ['label' => 'Helpers', 'url' => ['bucket/index2'], 'icon' => ''],
                    //         ['label' => 'Users', 'url' => ['minio-user/index2'], 'icon' => ''],
                    //     ]
                    // ],


                    ['label' => 'System Settings', 'header' => true],
                    [
                        'label' => 'System Settings',
                        'icon' => 'cog',
                        'items' => [
                            [
                                'label' => 'User & Group',
                                'icon' => '',
                                'items' => [
                                    ['label' => 'Group', 'url' => ['group/index'], 'icon' => ''],
                                    ['label' => 'Users', 'url' => ['user/index'], 'icon' => ''],
                                    ['label' => 'Permission', 'url' => ['permission/index'], 'icon' => ''],
                                ]
                                ],
                                ['label' => 'MinIO Install', 'url' => ['install/index'], 'icon' => ''],
                            ]
                    ],


                    // ['label' => 'Tester Menu', 'header' => true],
                    // ['label' => 'Simple Link', 'icon' => 'th', 'badge' => ''],

                    // ['label' => 'MULTI LEVEL EXAMPLE', 'header' => true],

                ],
            ]);
            ?>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>