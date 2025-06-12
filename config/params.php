<?php

return [
    'projectName' => $_ENV['APP_NAME'] ?? 'Default_Name',
    'projectShort' => $_ENV['APP_SHORT'] ?? 'Default_Short',
    'adminEmail' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',

    'minioActions' => [
        '*'         => '*',
        'GetObject'   => 'Get Read & Download Files',
        'PutObject'   => 'Put Write & Upload Files',
        'ListBucket'  => 'List Bucket',
        'DeleteObject' => 'Delete Files',
        'CopyObject' => 'Copy allows you to copy an object within S3 Files is Bucket',

    ],
];
