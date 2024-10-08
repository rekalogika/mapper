<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests/bin',
        __DIR__ . '/tests/config',
        __DIR__ . '/tests/src',
    ])
    ->withSkipPath(__DIR__ . '/tests/config/rekalogika-mapper/generated-mappings.php')
    ->withImportNames(importShortClasses: false)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        strictBooleans: true,
        symfonyCodeQuality: true,
        doctrineCodeQuality: true,
    )
    ->withPhpSets(php82: true)
    ->withRules([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withSkip([
        // static analysis tools don't like this
        RemoveUnusedVariableAssignRector::class,

        // static analysis tools don't like this
        RemoveNonExistingVarAnnotationRector::class,

        // cognitive burden to many people
        SimplifyIfElseToTernaryRector::class,

        // potential cognitive burden
        FlipTypeControlToUseExclusiveTypeRector::class,

        // results in too long variables
        CatchExceptionNameMatchingTypeRector::class,

        // makes code unreadable
        DisallowedShortTernaryRuleFixerRector::class,

        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/src/Proxy/Implementation/ProxyFactory.php',
            __DIR__ . '/src/Transformer/Context/AttributesTrait.php',
        ],

        RemoveUnusedPrivatePropertyRector::class => [
            __DIR__ . '/tests/src/Fixtures/AccessMethods/ObjectWithVariousAccessMethods.php',
        ],

        RemoveUnusedPrivateMethodParameterRector::class => [
            __DIR__ . '/src/DependencyInjection/RekalogikaMapperExtension.php',
        ],

        MakeInheritedMethodVisibilitySameAsParentRector::class => [
            __DIR__ . '/tests/src/Common/MapperTestFactory.php',
        ],

        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/tests/src/Fixtures/MapPropertyPathDto/BookWithMapInUnpromotedConstructorDto.php',
            __DIR__ . '/tests/src/Fixtures/MapAttribute/SomeObjectWithUnpromotedConstructorDto.php',
        ],
    ]);
