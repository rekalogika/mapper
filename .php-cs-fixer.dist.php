<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/config')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PhpCsFixer' => true,
    'declare_strict_types' => true,
    'single_line_comment_style' => false,
    'phpdoc_to_comment' => false,
    'php_unit_test_class_requires_covers' => false,
    'ordered_class_elements' => [
        'order' => ['use_trait']
    ],
    'string_implicit_backslashes' => false,
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line'
    ],

    'header_comment' => [
            'header' => <<<EOF
This file is part of rekalogika/mapper package.

(c) Priyadi Iman Nurcahyo <https://rekalogika.dev>

For the full copyright and license information, please view the LICENSE file
that was distributed with this source code.
EOF,
        ]
    ])
    ->setFinder($finder)
;
