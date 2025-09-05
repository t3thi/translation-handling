<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

call_user_func(function () {
    ExtensionManagementUtility::addStaticFile(
        'translation_handling',
        'Configuration/TypoScript',
        'Translation Handling'
    );
});
