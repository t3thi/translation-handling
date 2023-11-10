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

namespace T3thi\TranslationHandling\Generator;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manage a page tree with all test / demo styleguide data
 *
 * @internal
 */
final class Generator
{
    public function create(string $type): string
    {
        $rootUid = 1;
        $basePath = $type;
        // Create site configuration for frontend
        if (isset($GLOBALS['TYPO3_REQUEST']) && empty($basePath)) {
            $port = $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() ? ':' . $GLOBALS['TYPO3_REQUEST']->getUri()->getPort(
                ) : '';
            $domain = $GLOBALS['TYPO3_REQUEST']->getUri()->getScheme() . '://' . $GLOBALS['TYPO3_REQUEST']->getUri(
                )->getHost() . $port . '/';
        } else {
            // On cli there is no TYPO3_REUQEST object
            $domain = empty($basePath) ? '/' : $basePath;
        }
        $this->createSiteConfiguration($rootUid, $type, $domain, 'TYPO3 Translation Handling - ' . $type);

        return 'page created';
    }

    public function delete(): string
    {
        // Delete site configuration
        try {
            $rootUid = 1;

            if (!empty($rootUid)) {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($rootUid);
                $identifier = $site->getIdentifier();
                GeneralUtility::makeInstance(SiteConfiguration::class)->delete($identifier);
            }
        } catch (SiteNotFoundException $e) {
            // Do not throw a thing if site config does not exist
        }

        return 'page deleted';
    }

    /**
     * Create a site configuration on new styleguide root page
     */
    protected function createSiteConfiguration(
        int $rootPageId,
        string $type,
        string $base = 'http://localhost/',
        string $title = 'TYPO3 Translation Handling'
    ): void {
        // When the DataHandler created the page tree, a default site configuration has been added. Fetch,  rename, update.
        $siteIdentifier = 'translation-handling-' . $type . '-' . $rootPageId;
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($rootPageId);
            $siteConfiguration->rename($site->getIdentifier(), $siteIdentifier);
        } catch (SiteNotFoundException $e) {
            // Do not rename, just write a new one
        }
        $highestLanguageId = 99;
        $configuration = [
            'base' => $base . $siteIdentifier,
            'rootPageId' => $rootPageId,
            'routes' => [],
            'websiteTitle' => $title . ' ' . $rootPageId,
            'baseVariants' => [],
            'errorHandling' => [],
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => 'English',
                    'hreflang' => 'en-us',
                    'direction' => 'ltr',
                    'flag' => 'us',
                    'websiteTitle' => '',
                ],
                [
                    'title' => 'styleguide demo language danish',
                    'enabled' => true,
                    'base' => '/da/',
                    'typo3Language' => 'da',
                    'locale' => 'da_DK.UTF-8',
                    'iso-639-1' => 'da',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => 'da-dk',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'dk',
                    'languageId' => $highestLanguageId + 1,
                ],
                [
                    'title' => 'styleguide demo language german',
                    'enabled' => true,
                    'base' => '/de/',
                    'typo3Language' => 'de',
                    'locale' => 'de_DE.UTF-8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => 'de-de',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'de',
                    'languageId' => $highestLanguageId + 2,
                ],
            ],
        ];
        $siteConfiguration->write($siteIdentifier, $configuration);
    }
}
