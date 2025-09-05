<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

call_user_func(function () {
    // Add a field to pages table to identify translation handling demo pages.
    // Field is handled by DataHandler and is not needed to be shown in BE, so it is of type "passthrough"
    $additionalColumns = [
        'tx_translationhandling_identifier' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ];
    ExtensionManagementUtility::addTCAcolumns('tt_content', $additionalColumns);
});
