<?php

$settings = [
    'repository' => '',
    'store_folders' => [],
    'hyva_themes' => [],
    'init_magepack' => false,
    'additional_shared_dirs' => [],
    'cache_warmer_sitemaps' => [
        'pub/media/sitemap/sitemap.xml'
    ]
];

$hosts = [
    'production' => [
        'hostname' => '',
        'user' => 'app',
        'path' => '~/magento2'
    ],
    'staging' => [
        'hostname' => '',
        'user' => 'app',
        'path' => '~/magento2_staging'
    ]
];
