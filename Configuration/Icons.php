<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tt_content-t3thi_irrecontent' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:translation_handling/Resources/Public/Icons/ContentElement/IrreContent.svg',
    ],
    't3thi_irrecontent_children' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:translation_handling/Resources/Public/Icons/Record/IrreChild.svg',
    ],
];
