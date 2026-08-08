<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // Intentionally non-existent class names used to test behavior with missing classes/dependencies.
    ->ignoreUnknownClasses(['NonExisitng', 'NonExisting', 'NotExist1', 'NotExist2']);
