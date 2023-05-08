<?php
/**
 * Copyright (c) 2023. Taurus. All rights reserved
 * @author Alex Tolochko
 */

namespace Deployer;

require 'recipe/magento2.php';

// General deployment settings
set('keep_releases', 5);
set('writable_mode', 'chmod');
set('default_timeout', 1200);
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader');

// Static content settings
set('static_content_locales', '');
set('static_content_jobs', '4');

// list of store folders separated with whitespaces (Example: 'fr nl be germany')
set('store_folders', '');

// Magepack settings
set('init_magepack', false);

// Hyva settings
set('hyva_themes', '');

//
// Custom tasks
//

// Unlock deployment if it is failed
after('deploy:failed', 'deploy:unlock');

// Create symlinks for store folders
task('deploy:create-store-folders', function () {
    $storeFolders = get('store_folders', '');
    if (!empty($storeFolders)) {
        $storeFolders = explode(' ', trim($storeFolders));

        cd('{{release_path}}/pub');
        foreach ($storeFolders as $storeFolder) {
            run('ln -s ../pub ' . $storeFolder);
        }
    }
});
after('deploy:shared', 'deploy:create-store-folders');

// Cache warmer task
task('deploy:warm-cache', function () {
    cd('{{current_path}}');
    run('./cache-warmer.py --threads 4 --file pub/media/sitemap/sitemap.xml -v');
});

// MagePack compilation
after('deploy:magento', 'deploy:magepack');
task('deploy:magepack', function () {
    if (get('init_magepack')) {
        cd('{{release_path}}');
        run('npm install');
        run('./node_modules/.bin/magepack bundle');
    } else {
        writeln('MagePack is not configured!');
    }
});

// Hyva themes compilation
task('deploy:hyva', function () {
    $hyvaThemes = get('hyva_themes', '');
    if (!empty($hyvaThemes)) {
        $hyvaThemes = explode(' ', $hyvaThemes);
        foreach ($hyvaThemes as $theme) {
            cd('{{release_path}}/app/design/frontend/' + $theme + '/web/tailwind');
            run('npm install');
            run('npm run build-prod');
        }
    }
});
before('deploy:magento', 'deploy:hyva');
