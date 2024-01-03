<?php

namespace Deployer;

require 'recipe/magento2.php';

// Default settings
set('keep_releases', 5);
set('writable_mode', 'chmod');
set('default_timeout', 1200);
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction');
set('static_content_locales', '');
set('static_content_jobs', '4');
set('store_folders', '');
set('hyva_themes', '');
set('init_magepack', false);

//
// Custom tasks
//

/**
 * Create symlinks for store folders
 */
task('deploy:create-store-folders', function () {
    $storeFolders = get('store_folders', []);
    if (is_string($storeFolders)) {
        $storeFolders = explode(' ', trim($storeFolders));
    }

    if (count($storeFolders) > 0) {
        cd('{{release_path}}/pub');
        foreach ($storeFolders as $storeFolder) {
            run('ln -s ../pub ' . $storeFolder);
        }
    }
});
after('deploy:shared', 'deploy:create-store-folders');

/**
 * MagePack compilation
 */
task('deploy:magepack', function () {
    if (get('init_magepack')) {
        cd('{{release_path}}');
        run('npm install');
        run('./node_modules/.bin/magepack bundle');
    } else {
        writeln('MagePack is not configured!');
    }
});
after('magento:deploy:assets', 'deploy:magepack');

/**
 * Hyva themes compilation
 */
task('deploy:hyva', function () {
    $hyvaThemes = get('hyva_themes', []);
    if (is_string($hyvaThemes)) {
        $hyvaThemes = explode(' ', $hyvaThemes);
    }

    if (count($hyvaThemes) > 0) {
        foreach ($hyvaThemes as $theme) {
            cd('{{release_path}}/app/design/frontend/' . $theme . '/web/tailwind');
            run('npm install');
            run('npm run build-prod');
        }
    } else {
        writeln('No Hyva themes configured!');
    }
});
before('deploy:magento', 'deploy:hyva');

/**
 * Cache warmer
 */
task('deploy:cache-warmer', function () {
    if (count(get('cache_warmer_sitemaps')) > 0) {
        cd('{{current_path}}');
        run('echo -e "\n" >> {{deploy_path}}/.dep/cache_warmer.log 2>&1');
        run('date +"[%d-%m-%y %T]" >> {{deploy_path}}/.dep/cache_warmer.log 2>&1');
        foreach (get('cache_warmer_sitemaps') as $sitemapPath) {
            run('./cache-warmer.py --threads 4 --file ' . $sitemapPath . ' -v >> {{deploy_path}}/.dep/cache_warmer.log 2>&1 &');
        }
        writeln('Cache warmer has been started in background mode. Logs will be written to: {{deploy_path}}/.dep/cache_warmer.log');
    } else {
        writeln('No sitemaps configured for cache warmer.');
    }
});
after('deploy:publish', 'deploy:cache-warmer');

// Rewrite 'magento:deploy:assets' task to add possibility to compile each theme's content separately
task('magento:deploy:assets', function () {
    $themesToCompile = '';
    if (count(get('magento_themes')) > 0) {
        foreach (get('magento_themes') as $theme) {
            run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:static-content:deploy --content-version={{content_version}} -t $theme -f -j  {{static_content_jobs}}");
        }
    } else {
        run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:static-content:deploy --content-version={{content_version}} {{static_content_locales}} -j {{static_content_jobs}}");
    }
});

// Process custom shared folders defined in 'additional_shared_dirs'
before('deploy:shared', 'deploy:additional-shared');

// Unlock deployment if it is failed
after('deploy:failed', 'deploy:unlock');

//
// Extend default settings
//
require 'deploy-config.php';

foreach ($settings as $key => $value) {
    set($key, $value);
}

foreach ($hosts as $alias => $host) {
    host($alias)
        ->setHostname($host['hostname'])
        ->setRemoteUser($host['user'])
        ->setDeployPath($host['path']);
}
