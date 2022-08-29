<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    // path to phpstan config
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');

    // add src and tests folder as refactoring targets
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_72,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
    ]);

    // skip closure transformation
    $rectorConfig->skip([
        StaticClosureRector::class,
        SimplifyIfElseToTernaryRector::class,
    ]);

    $rectorConfig->importNames();
};
