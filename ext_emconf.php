<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'translation_handling',
    'description' => 'TYPO3 extension to showcase TYPO3 translation handling capabilities',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'T3thi\\TranslationHandling\\' => 'Classes/',
        ],
    ],
];
