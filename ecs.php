<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([__DIR__ . "/src", __DIR__ . "/tests"]);

    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
        "syntax" => "short",
    ]);


    $ecsConfig->rules([
        // FullyQualifiedStrictTypesFixer::class,
    ]);

    $ecsConfig->skip([
        // ClassAttributesSeparationFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
    ]);

    $ecsConfig->sets([
        SetList::SPACES,
        SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::PSR_12,
    ]);

    $ecsConfig->ruleWithConfiguration(PhpdocLineSpanFixer::class, [
        "property" => "single",
        "const" => "single",
    ]);
};
