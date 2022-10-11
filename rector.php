<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->phpstanConfig(__DIR__ . "/phpstan.neon");

    $rectorConfig->paths([
        __DIR__ . "/src",
        __DIR__ . "/tests",
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_72,
        SetList::CODE_QUALITY,
        //SetList::DEAD_CODE,
        //SetList::PRIVATIZATION,
        //SetList::NAMING,
        //SetList::TYPE_DECLARATION,
        //SetList::EARLY_RETURN,
        //SetList::TYPE_DECLARATION_STRICT,
        SetList::DEAD_CODE,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        //PHPUnitSetList::PHPUNIT_90,
        SetList::CODING_STYLE,
    ]);

    $rectorConfig->skip([
        StaticClosureRector::class,
        SimplifyIfElseToTernaryRector::class,
        AddSeeTestAnnotationRector::class,
    ]);

    $rectorConfig->importNames();
};
