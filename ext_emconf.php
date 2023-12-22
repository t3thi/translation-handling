<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Translation Handling',
    'description' => 'TYPO3 extension to showcase TYPO3 translation handling capabilities',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99',
            'fluid_styled_content' => '12.0.0-13.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'T3thi\\TranslationHandling\\' => 'Classes/',
        ],
    ],
];
