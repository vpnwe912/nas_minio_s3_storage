<?php

return [
    'projectName' => $_ENV['APP_NAME'] ?? 'Default_Name',
    'projectShort' => $_ENV['APP_SHORT'] ?? 'Default_Short',
    'adminEmail' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',
];
