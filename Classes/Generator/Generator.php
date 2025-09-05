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

use T3thi\TranslationHandling\Content\Content;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Manage a page tree with all test / demo translationhandling data
 */
final class Generator
{
    public const T3THI_FIELD = 'tx_translationhandling_identifier';
    protected DataHandler $dataHandler;
    protected $recordFinder;

    public function __construct(?RecordFinder $recordFinder = null)
    {
        $this->recordFinder = $recordFinder;
    }

    public function create(string $type, string $basePath = ''): string
    {
        $fileHandler = GeneralUtility::makeInstance(FileHandler::class);
        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $title = 'TYPO3 Translation Handling - ' . strtoupper($type);

        // Create should not be called if demo frontend data exists already
        if (count($this->recordFinder->findUidsOfPages([$t3thiIdentifier . '_root'], self::T3THI_FIELD))) {
            throw new Exception(
                'Can not create a second record tree for ' . $type,
                1702623429
            );
        }

        // Add files
        $fileHandler->addToFal([
            'Superhero_00032_.jpg',
        ], 'EXT:translation_handling/Resources/Public/Images/', 'translation_handling');

        // Add entry page on top level
        $newIdOfEntryPage = StringUtility::getUniqueId('NEW');
        $newIdOfRecordsStorage = StringUtility::getUniqueId('NEW');
        $newIdOfRootTsTemplate = StringUtility::getUniqueId('NEW');

        $data = [
            'pages' => [
                $newIdOfEntryPage => [
                    'title' => $title,
                    'pid' => 0 - $this->recordFinder->getUidOfLastTopLevelPage(),
                    // Define page as translation handling demo
                    self::T3THI_FIELD => $t3thiIdentifier . '_root',
                    'is_siteroot' => 1,
                    'hidden' => 0,
                ],
                // Storage for records
                $newIdOfRecordsStorage => [
                    'title' => 'records storage',
                    'pid' => $newIdOfEntryPage,
                    self::T3THI_FIELD => $t3thiIdentifier . '_storage',
                    'hidden' => 0,
                    'doktype' => 254,
                ],
            ],
            'sys_template' => [
                $newIdOfRootTsTemplate => [
                    'title' => 'Root Translation Handling ' . ucfirst($type),
                    'root' => 1,
                    'clear' => 3,
                    'include_static_file' => 'EXT:translation_handling/Configuration/TypoScript,EXT:seo/Configuration/TypoScript/XmlSitemap',
                    'constants' => '',
                    'config' => '',
                    'pid' => $newIdOfEntryPage,
                ],
            ],
        ];

        $this->executeDataHandler($data);

        // Keep uids of created pages for translation, adding subpages and content
        $rootPageUid = $this->dataHandler->substNEWwithIDs[$newIdOfEntryPage] ?? $newIdOfEntryPage;
        $storagePageUid = $this->dataHandler->substNEWwithIDs[$newIdOfRecordsStorage] ?? $newIdOfRecordsStorage;

        // Create site configuration for frontend
        if (isset($GLOBALS['TYPO3_REQUEST']) && empty($basePath)) {
            $port = $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() ? ':' . $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() : '';
            $domain = $GLOBALS['TYPO3_REQUEST']->getUri()->getScheme() . '://' . $GLOBALS['TYPO3_REQUEST']->getUri()->getHost() . $port . '/';
        } else {
            // On cli there is no TYPO3_REQUEST object
            $domain = empty($basePath) ? '/' : $basePath;
        }

        $this->createSiteConfiguration($rootPageUid, $type, $domain, $title);

        // Add content to root page
        $rootContentElements = $this->addContentToPage(
            Content::getRootContent($type),
            $rootPageUid,
            $t3thiIdentifier
        );

        // Build page tree data array for translation
        $pageTreeData['root'] = [
            'uid' => $rootPageUid,
            'contentElements' => $rootContentElements,
        ];
        $pageTreeData['storage'] = [
            'uid' => $storagePageUid,
        ];

        // Add subpages with content elements per backend translation mode
        $translationModes = [
            'free',
            'connected',
            'mixed',
        ];
        foreach ($translationModes as $mode) {
            $newIdOfPage = StringUtility::getUniqueId('NEW');
            // Add subpage for each mode
            $pageData = [
                'pages' => [
                    $newIdOfPage => [
                        'title' => $mode,
                        'pid' => $rootPageUid,
                        self::T3THI_FIELD => $t3thiIdentifier,
                        'hidden' => 0,
                    ],
                ],
            ];
            $this->executeDataHandler($pageData);

            // Keep uid of created page for adding content elements and translation
            $pageUid = $this->dataHandler->substNEWwithIDs[$newIdOfPage] ?? $newIdOfPage;

            // Add content elements to each page
            $contentElements = $this->addContentToPage(Content::getContent(), $pageUid, $t3thiIdentifier);

            // Keep subpages data for translation
            $pageTreeData['pages-' . $pageUid] = [
                'uid' => $pageUid,
                'mode' => $mode,
                'contentElements' => $contentElements,
            ];
        }

        // Add files to existing content elements and pages
        $this->executeDataHandler($fileHandler->getFalDataForContent($type, self::T3THI_FIELD));
        $this->executeDataHandler($fileHandler->getFalDataForPages($type, self::T3THI_FIELD));

        // Write uid into header field (for identification with fallbacks)
        $this->executeDataHandler($this->getContentHeaderData($type));

        // Localize page tree
        $this->localizePageTree($pageTreeData);

        // Todo: Add menus
        // Todo: Add records
        // Todo: Add CE with IRRE (and maybe nested IRRE)
        // Todo: Add IRRE to page
        return 'page for type ' . $type . ' created';
    }

    public function delete(string $type): string
    {
        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $commands = [];

        // Delete pages - also deletes tt_content, sys_category and sys_file_references
        $frontendPagesUids = $this->recordFinder->findUidsOfPages([
            $t3thiIdentifier . '_root',
            $t3thiIdentifier . '_storage',
            $t3thiIdentifier,
        ], self::T3THI_FIELD);
        if (!empty($frontendPagesUids)) {
            foreach ($frontendPagesUids as $page) {
                $commands['pages'][(int)$page]['delete'] = 1;
            }
        }

        // Delete site configuration
        try {
            $rootUid = $this->recordFinder->findUidsOfPages([$t3thiIdentifier . '_root'], self::T3THI_FIELD);

            if (!empty($rootUid)) {
                $site = GeneralUtility::makeInstance(SiteFinder::class)?->getSiteByRootPageId((int)$rootUid[0]);
                $identifier = $site->getIdentifier();
                GeneralUtility::makeInstance(SiteWriter::class)?->delete($identifier);
            }
        } catch (SiteNotFoundException $e) {
            // Do not throw a thing if site config does not exist
        }
        // TODO: delete site configuration folder

        // Delete records data
        $this->executeDataHandler([], $commands);

        // Delete created files
        $fileHandler = GeneralUtility::makeInstance(FileHandler::class);
        $fileHandler->deleteFalFolder('translation_handling');

        return 'page for type ' . $type . ' deleted';
    }

    /**
     * Create a site configuration on new translation handling root page
     */
    protected function createSiteConfiguration(
        int $rootPageId,
        string $type,
        string $base = '/',
        string $title = 'TYPO3 Translation Handling'
    ): void {
        // When the DataHandler created the page tree, a default site configuration has been added. Fetch,  rename, update.
        $siteIdentifier = 'translation-handling-' . $type . '-' . $rootPageId;
        $siteConfiguration = GeneralUtility::makeInstance(SiteWriter::class);
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)?->getSiteByRootPageId($rootPageId);
            $siteConfiguration->rename($site->getIdentifier(), $siteIdentifier);
        } catch (SiteNotFoundException $e) {
            // Do not rename, just write a new one
        }
        $highestLanguageId = $this->recordFinder->findHighestLanguageId();
        $configuration = [
            'base' => $base . $siteIdentifier,
            'rootPageId' => $rootPageId,
            'routes' => [],
            'websiteTitle' => $title,
            'baseVariants' => [],
            'errorHandling' => [],
            'languages' => [
                [
                    'title' => 'Pink (Default)',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.utf8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => '',
                    'hreflang' => 'pink',
                    'direction' => 'ltr',
                    'flag' => 'pink',
                    'websiteTitle' => strtoupper($type) . ' Pink (default lang)',
                ],
                [
                    'title' => 'Purple (> Pink)',
                    'enabled' => true,
                    'base' => '/purple/',
                    'typo3Language' => 'es',
                    'locale' => 'es_US.utf8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => strtoupper($type) . ' Purple (fallback to default)',
                    'navigationTitle' => '',
                    'hreflang' => 'pruple',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => '0',
                    'flag' => 'purple',
                    'languageId' => $highestLanguageId + 1,
                ],
                [
                    'title' => 'Indigo (> Purple > Pink)',
                    'enabled' => true,
                    'base' => '/indigo/',
                    'typo3Language' => 'es',
                    'locale' => 'es_MX.utf8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => strtoupper($type) . ' Indigo (fallback to translation to default)',
                    'navigationTitle' => '',
                    'hreflang' => 'indigo',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => $highestLanguageId + 1 . ',0',
                    'flag' => 'indigo',
                    'languageId' => $highestLanguageId + 2,
                ],
                [
                    'title' => 'Yellow (> Teal > Green > Pink)',
                    'enabled' => true,
                    'base' => '/yellow/',
                    'typo3Language' => 'es',
                    'locale' => 'es_ES.utf8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => strtoupper($type) . ' Yellow (fallback to translations with higher languageId twice then to default)',
                    'navigationTitle' => '',
                    'hreflang' => 'yellow',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    // todo: there is some sorting of fallbacks in the core?
                    'fallbacks' => implode(',', [$highestLanguageId + 4, $highestLanguageId + 5, 0]),
                    'flag' => 'yellow',
                    'languageId' => $highestLanguageId + 3,
                ],
                // (todo: first to 0 then to translation ???)
                [
                    'title' => 'Teal',
                    'enabled' => true,
                    'base' => '/teal/',
                    'typo3Language' => 'de',
                    'locale' => 'de_DE.utf8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => strtoupper($type) . ' Teal (no fallback)',
                    'navigationTitle' => '',
                    'hreflang' => 'teal',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => '',
                    'flag' => 'teal',
                    'languageId' => $highestLanguageId + 4,
                ],
                [
                    'title' => 'Green (> Teal)',
                    'enabled' => true,
                    'base' => '/green/',
                    'typo3Language' => 'de',
                    'locale' => 'de_AT.utf8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => strtoupper($type) . ' Green (fallback to translation no default)',
                    'navigationTitle' => '',
                    'hreflang' => 'green',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => $highestLanguageId + 4,
                    'flag' => 'green',
                    'languageId' => $highestLanguageId + 5,
                ],
                [
                    'title' => 'Cyan (> Teal > Green)',
                    'enabled' => true,
                    'base' => '/cyan/',
                    'typo3Language' => 'de',
                    'locale' => 'de_CH.utf8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => strtoupper($type) . ' Green (fallback to two translations no default)',
                    'navigationTitle' => '',
                    'hreflang' => 'cyan',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => implode(',', [$highestLanguageId + 5, $highestLanguageId + 4]),
                    'flag' => 'cyan',
                    'languageId' => $highestLanguageId + 6,
                ],
            ],
        ];
        $siteConfiguration->write($siteIdentifier, $configuration);
    }

    public function executeDataHandler(array $data = [], array $commands = []): void
    {
        if (!empty($data) || !empty($commands)) {
            $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $this->dataHandler->enableLogging = false;
            $this->dataHandler->bypassAccessCheckForRecords = true;
            $this->dataHandler->bypassWorkspaceRestrictions = true;
            $this->dataHandler->start($data, $commands);
            if (Environment::isCli()) {
                $this->dataHandler->clear_cacheCmd('all');
            }

            empty($data) ?: $this->dataHandler->process_datamap();
            empty($commands) ?: $this->dataHandler->process_cmdmap();

            // Update signal only if not running in cli mode
            if (!Environment::isCli()) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }
        }
    }

    /**
     * Localize records depending on backend translation mode
     *
     * @throws Exception
     */
    protected function generateTranslatedRecords(
        string $tableName,
        int $uid,
        int $languageId,
        string $translationMode
    ): void {
        if (!BackendUtility::isTableLocalizable($tableName)) {
            return;
        }

        switch ($translationMode) {
            case 'free':
                // Free translation mode: copy to language
                $this->localizeRecord($tableName, $uid, $languageId, 'copyToLanguage');
                break;
            case 'connected':
                // Connected translation mode: translate
                $this->localizeRecord($tableName, $uid, $languageId, 'localize');
                break;
            case 'mixed':
                // Mixed mode: switch randomly between copying and translating
                $rand = rand() & 1;
                switch ($rand) {
                    case 1:
                        $this->localizeRecord($tableName, $uid, $languageId, 'localize');
                        break;
                    default:
                        $this->localizeRecord($tableName, $uid, $languageId, 'copyToLanguage');
                }
                break;
            default:
                throw new Exception(
                    'Unknown translation mode. ' . $translationMode,
                    1704469009
                );
        }
    }

    /**
     * Either localize or copyToLangaage single record
     */
    protected function localizeRecord(
        string $tableName,
        int $uid,
        int $languageId,
        string $mode
    ): void {
        $commandMap = [
            $tableName => [
                $uid => [
                    $mode => $languageId,
                ],
            ],
        ];
        $this->executeDataHandler([], $commandMap);
    }

    /**
     * Localize pageTree
     *
     * @throws Exception
     */
    protected function localizePageTree(array $pageTreeData): void
    {
        $rootPageUid = $pageTreeData['root']['uid'] ?? 0;
        $languageIds = $this->recordFinder->findLanguageIdsByRootPage($rootPageUid);
        if (empty($languageIds)) {
            throw new Exception(
                'ERROR - no language uids found',
                1704469009
            );
        }
        foreach ($pageTreeData as $page) {
            $translationMode = $page['mode'] ?? 'connected';
            $pageUid = $page['uid'] ?? 0;
            if ($pageUid === 0) {
                continue;
            }
            foreach ($languageIds as $languageId) {
                // Localize root page and subpages
                $commands = [];
                $commands['pages'][$pageUid]['localize'] = $languageId;
                $this->executeDataHandler([], $commands);

                // Localize content for page to all languages
                $contentElements = $page['contentElements'] ?? [];
                foreach ($contentElements as $contentElement) {
                    // Don't translate if language is listed in excludeLanguages
                    $excludeLanguages = $contentElement['config']['excludeLanguages'] ?? [];
                    if (!is_array($excludeLanguages)) {
                        throw new Exception(
                            'type error: excludeLanguages for content must be array ',
                            1704540645
                        );
                    }
                    if (in_array($languageId, $excludeLanguages)) {
                        continue;
                    }
                    $this->generateTranslatedRecords(
                        'tt_content',
                        $contentElement['uid'],
                        $languageId,
                        $translationMode
                    );
                }
            }
        }
    }

    /**
     * Add content elements to page
     */
    protected function addContentToPage(array $contentElements, int $pageUid, string $t3thiIdentifier): array
    {
        $createdContentElements = [];
        foreach ($contentElements as $cType => $ce) {
            foreach ($ce as $content) {
                $newIdOfContent = StringUtility::getUniqueId('NEW');
                $contentData = [
                    'tt_content' => [
                        $newIdOfContent => array_merge(
                            [
                                'CType' => $cType,
                                'pid' => $pageUid,
                                self::T3THI_FIELD => $t3thiIdentifier,
                            ],
                            $this->cleanContentData($content)
                        ),
                    ],
                ];
                $this->executeDataHandler($contentData);

                // Keep uids and config of created content elements
                $createdContentElements[] = [
                    'uid' => $this->dataHandler->substNEWwithIDs[$newIdOfContent] ?? $newIdOfContent,
                    'config' => $content['config'] ?? [],
                ];
            }
        }

        return $createdContentElements;
    }

    /**
     * Remove configuration keys from content array
     * that should not be passed to DataHandler
     */
    protected function cleanContentData(array $content): array
    {
        if (isset($content['config'])) {
            unset($content['config']);
        }

        return $content;
    }

    protected function getContentHeaderData(string $type): array
    {
        $recordData = [];
        foreach ($this->recordFinder->findTtContent($type, self::T3THI_FIELD, []) as $content) {
            $recordData['tt_content'][$content['uid']]['header'] = $content['uid'];
        }
        return $recordData;
    }
}
