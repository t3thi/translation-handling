<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function () {
    ExtensionManagementUtility::addStaticFile(
        'translation_handling',
        'Configuration/TypoScript',
        'Translation Handling'
    );
})();
