<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['LOG']['T3thi']['TranslationHandling']['writerConfiguration'] = [
    LogLevel::WARNING => [
        FileWriter::class => [
            'logFile' => Environment::getVarPath() . '/log/translation_handling.log',
        ],
    ],
];
