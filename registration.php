<?php
/**
 * Copyright (c) 2023. Taurus. All rights reserved
 * @author Alex Tolochko
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Taurus_Deployer',
    __DIR__
);
