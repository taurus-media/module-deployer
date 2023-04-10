<?php
/**
 * Copyright (c) 2023. Taurus. All rights reserved
 * @author Alex Tolochko
 */

namespace Taurus\Deployer\Plugin\Setup\Declaration\Schema\Dto;

use Magento\Framework\Setup\Declaration\Schema\Dto\Table;

class TablePlugin
{
    /**
     * Fix compatibility with Percona DB. When running 'bin/magento setup:db:status' vs Perconda,
     * it always says that there are schema differences related to tables charset.
     *
     * @param Table $subject
     * @param $params
     * @return mixed
     */
    public function afterGetDiffSensitiveParams(Table $subject, $params)
    {
        if (isset($params['charset']) && $params['charset'] === 'utf8mb3') {
            $params['charset'] = 'utf8';
        }

        return $params;
    }
}
