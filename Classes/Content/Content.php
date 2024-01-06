<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace T3thi\TranslationHandling\Content;

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Get data for content elements
 */
final class Content
{
    /**
     * Get content elements for root pages
     *
     * @return array
     */
    public static function getRootContent(string $type): array
    {
        return [
            'textmedia' => [
                [
                    'header' => Kauderwelsch::getIntroHeader(),
                    'header_layout' => 2,
                    'bodytext' => Kauderwelsch::getIntroText($type),
                ],
            ],
        ];
    }

    /**
     * Get content elements for subpages
     * additional key config is used for configuration of translation handling
     * it is removed before passing data to Datahandler
     *
     * @return array[]
     */
    public static function getContent(): array
    {
        // TODO: maybe config->excludeTypes? For types strict and free we don't need that many CEs
        return [
            'text' => [
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                ],
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                    'config' => [
                        'excludeLanguages' => [1, 2, 3, 4, 5, 6],
                    ],
                ],
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                    'config' => [
                        'excludeLanguages' => [4, 5],
                    ],
                ],
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                    'config' => [
                        'excludeLanguages' => [5],
                    ],
                ],
            ],
            'textmedia' => [
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'subheader' => Kauderwelsch::getLoremIpsum(20),
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                    'imageorient' => 17,
                    'config' => [
                        'excludeLanguages' => [1],
                    ],
                ],
                [
                    'header' => Kauderwelsch::getLoremIpsum(20),
                    'header_layout' => 3,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml(true),
                    'imageorient' => 18,
                    'config' => [
                        'excludeLanguages' => [4],
                    ],
                ],
            ],
        ];
    }
}
