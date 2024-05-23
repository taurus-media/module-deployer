<?php
/**
 * Copyright (c) 2024. Taurus. All rights reserved
 * @author Alex Tolochko
 */

namespace Taurus\Deployer\Plugin\Setup\Declaration\Schema;

use Magento\Framework\Setup\Declaration\Schema\Comparator;
use Magento\Framework\Setup\Declaration\Schema\Dto\ElementInterface;

class ComparatorPlugin
{
    public function aroundCompare(Comparator $subject, callable $proceed, ElementInterface $first, ElementInterface $second)
    {
        $firstParams = $this->prepareParams($first->getDiffSensitiveParams());
        $secondParams = $this->prepareParams($second->getDiffSensitiveParams());

        return get_class($first) === get_class($second) &&
            $firstParams === $secondParams;
    }

    private function prepareParams($params)
    {
        if (isset($params['charset']) && $params['charset'] === 'utf8mb3') {
            $params['charset'] = 'utf8';
        }

        if (isset($params['collation']) && $params['collation'] === 'utf8mb3_general_ci') {
            $params['collation'] = 'utf8_general_ci';
        }

        if (isset($params['comment']) && $params['comment'] === '') {
            $params['comment'] = null;
        }

        return $params;
    }
}
