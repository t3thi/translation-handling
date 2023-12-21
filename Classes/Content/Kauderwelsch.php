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
 * Get test strings
 *
 * @internal
 */
final class Kauderwelsch
{
    /**
     * Lorem ipsum test with fixed length.
     *
     * @return string
     */
    public static function getLoremIpsum(): string
    {
        return 'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.';
    }

    /**
     * Lorem ipsum test with fixed length and HTML in it.
     *
     * @return string
     */
    public static function getLoremIpsumHtml(): string
    {
        return 'Bacon ipsum dolor sit <strong>strong amet capicola</strong> jerky pork chop rump shoulder shank. Shankle strip <a href="#">steak pig salami link</a>. Leberkas shoulder ham hock cow salami bacon <em>em pork pork</em> chop, jerky pork belly drumstick ham. Tri-tip strip steak sirloin prosciutto pastrami. Corned beef venison tenderloin, biltong meatball pork tongue short ribs jowl cow hamburger strip steak. Doner turducken jerky short loin chuck filet mignon.';
    }

    /**
     * Get a single word
     *
     * @return string
     */
    public static function getWord(): string
    {
        return 'lipsum';
    }

    /**
     * Get a single password
     */
    public static function getPassword(): string
    {
        return 'somePassword1!';
    }

    /**
     * Get an integer
     *
     * @return int
     */
    public static function getInteger(): int
    {
        return 42;
    }

    /**
     * Timestamp of a day before 1970
     *
     * @return int
     */
    public static function getDateTimestamp(): int
    {
        // 1960-1-1 00:00:00 GMT
        return -315619200;
    }

    /**
     * Timestamp of a day before 1970 with seconds
     *
     * @return int
     */
    public static function getDatetimeTimestamp(): int
    {
        // 1960-1-1 05:23:42 GMT
        return -315599778;
    }

    /**
     * Date before 1970 as string
     *
     * @return string
     */
    public static function getDateString(): string
    {
        // GMT
        return '1960-01-01';
    }

    /**
     * Date before 1970 with seconds as string
     *
     * @return string
     */
    public static function getDatetimeString(): string
    {
        // GMT
        return '1960-01-01 05:42:23';
    }

    /**
     * Get a float
     *
     * @return float
     */
    public static function getFloat(): float
    {
        return 5.23;
    }

    /**
     * Get a link
     *
     * @return string
     */
    public static function getLink(): string
    {
        return 'https://typo3.org';
    }

    /**
     * Get a valid email
     *
     * @return string
     */
    public static function getEmail(): string
    {
        return 'foo@example.com';
    }

    /**
     * Get a color as hex string
     *
     * @return string
     */
    public static function getHexColor(): string
    {
        return '#FF8700';
    }
}
