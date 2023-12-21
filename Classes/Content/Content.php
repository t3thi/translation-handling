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

/**
 * Get data for content elements
 *
 * @internal
 */
final class Content
{
    /**
     * Get content elements for home page
     *
     * @return array
     */
    public static function getHomeContent(): array
    {
        return [
            'textmedia' => [
                [
                    'header' => Kauderwelsch::getLoremIpsum(),
                    'header_layout' => 5,
                    'subheader' => Kauderwelsch::getLoremIpsum(),
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml() . ' ' . Kauderwelsch::getLoremIpsumHtml(),
                ],
                [
                    'header' => Kauderwelsch::getLoremIpsum(),
                    'header_layout' => 2,
                    'bodytext' => Kauderwelsch::getLoremIpsumHtml() . ' ' . Kauderwelsch::getLoremIpsumHtml(),
                    'imageorient' => 25,
                ],
            ],
        ];
    }
}
