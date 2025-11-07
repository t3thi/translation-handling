<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function () {
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

    // Add custom select item group "t3thi" to the tt_content CType field
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        't3thi',
        'LLL:EXT:translation_handling/Resources/Private/Language/locallang_db.xlf:tt_content.CType.group.t3thi',
    );

    // Register custom CType in tt_content.CType
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:translation_handling/Resources/Private/Language/locallang_db.xlf:tt_content.CType.t3thi_irrecontent',
            'description' => 'LLL:EXT:translation_handling/Resources/Private/Language/locallang_db.xlf:tt_content.CType.t3thi_irrecontent.description',
            'value' => 't3thi_irrecontent',
            'icon' => 'tt_content-t3thi_irrecontent',
            'group' => 't3thi',
        ]
    );

    //  Set icon in page/list module
    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['t3thi_irrecontent'] = 'tt_content-t3thi_irrecontent';

    // Add inline child field to tt_content
    ExtensionManagementUtility::addTCAcolumns(
        'tt_content',
        [
            't3thi_irrecontent_children' => [
                'exclude' => true,
                'label' => 'LLL:EXT:translation_handling/Resources/Private/Language/locallang_db.xlf:tt_content.t3thi_irrecontent_children',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 't3thi_irrecontent_children',
                    'foreign_field' => 'foreign_table_parent_uid',
                    'appearance' => [
                        'useSortable' => true,
                    ],
                ],
            ],
        ]
    );

    // Define full TCA type configuration for t3thi_irrecontent
    $GLOBALS['TCA']['tt_content']['types']['t3thi_irrecontent'] = [
        'showitem' => '
        --palette--;;general,
        header,
        t3thi_irrecontent_children,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
    ',
    ];
})();
