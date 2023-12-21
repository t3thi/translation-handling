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

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Manage a page tree with all test / demo translationhandling data
 *
 * @internal
 */
final class Generator
{
    public const T3THI_FIELD = 'tx_translationhandling_identifier';

    public function create(string $type): string
    {
        $basePath = $type;
        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $title = 'TYPO3 Translation Handling - ' . strtoupper($type);

        // Create should not be called if demo frontend data exists already
        if (count($this->findUidsOfPages([$t3thiIdentifier . '_root']))) {
            throw new Exception(
                'Can not create a second record tree for ' . $type,
                1702623429
            );
        }

        // Add entry page on top level
        $newIdOfEntryPage = StringUtility::getUniqueId('NEW');
        $newIdOfRecordsStorage = StringUtility::getUniqueId('NEW');
        $newIdOfRootTsTemplate = StringUtility::getUniqueId('NEW');

        $data = [
            'pages' => [
                $newIdOfEntryPage => [
                    'title' => $title,
                    'pid' => 0 - $this->getUidOfLastTopLevelPage(),
                    // Define page as translation handling demo
                    self::T3THI_FIELD => $t3thiIdentifier . '_root',
                    'is_siteroot' => 1,
                    'hidden' => 0,
                ],
                // Storage for records
                $newIdOfRecordsStorage => [
                    'title' => 'records storage',
                    'pid' => $newIdOfEntryPage,
                    self::T3THI_FIELD => $t3thiIdentifier,
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

        $contentData = $this->getElementContent();

        foreach ($contentData as $cType => $ce) {
            $newIdOfPage = StringUtility::getUniqueId('NEW');
            $data['pages'][$newIdOfPage] = [
                'title' => $cType,
                self::T3THI_FIELD => $t3thiIdentifier,
                'hidden' => 0,
                'abstract' => Kauderwelsch::getLoremIpsum(),
                'pid' => $newIdOfEntryPage,
            ];

            foreach ($ce as $content) {
                $newIdOfContent = StringUtility::getUniqueId('NEW');
                $data['tt_content'][$newIdOfContent] = $content;
                $data['tt_content'][$newIdOfContent]['CType'] = $cType;
                $data['tt_content'][$newIdOfContent]['pid'] = $newIdOfPage;

                $data['tt_content'][$newIdOfContent][self::T3THI_FIELD] = $t3thiIdentifier;
            }
        }

        $this->executeDataHandler($data);

        // Create site configuration for frontend
        if (isset($GLOBALS['TYPO3_REQUEST']) && empty($basePath)) {
            $port = $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() ? ':' . $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() : '';
            $domain = $GLOBALS['TYPO3_REQUEST']->getUri()->getScheme() . '://' . $GLOBALS['TYPO3_REQUEST']->getUri()->getHost() . $port . '/';
        } else {
            // On cli there is no TYPO3_REQUEST object
            $domain = empty($basePath) ? '/' : $basePath;
        }

        $rootPageUid = (int)$this->findUidsOfPages([$t3thiIdentifier . '_root'])[0];
        $this->createSiteConfiguration($rootPageUid, $type, $domain, $title);

        return 'page for type ' . $type . ' created';
    }

    public function delete(string $type): string
    {
        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $commands = [];

        // Delete pages - also deletes tt_content, sys_category and sys_file_references
        $frontendPagesUids = $this->findUidsOfPages([$t3thiIdentifier . '_root', $t3thiIdentifier]);
        if (!empty($frontendPagesUids)) {
            foreach ($frontendPagesUids as $page) {
                $commands['pages'][(int)$page]['delete'] = 1;
            }
        }

        // Delete site configuration
        try {
            $rootUid = $this->findUidsOfPages([$t3thiIdentifier . '_root']);

            if (!empty($rootUid)) {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId((int)$rootUid[0]);
                $identifier = $site->getIdentifier();
                GeneralUtility::makeInstance(SiteConfiguration::class)->delete($identifier);
            }
        } catch (SiteNotFoundException $e) {
            // Do not throw a thing if site config does not exist
        }

        // Delete records data
        $this->executeDataHandler([], $commands);

//        // Delete created files
//        $this->deleteFalFolder('translation_handling_' . $type);

        return 'page for type ' . $type . ' deleted';
    }

    /**
     * Create a site configuration on new translation handling root page
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
        $highestLanguageId = $this->findHighestLanguageId();
        $configuration = [
            'base' => $base . $siteIdentifier,
            'rootPageId' => $rootPageId,
            'routes' => [],
            'websiteTitle' => $title,
            'baseVariants' => [],
            'errorHandling' => [],
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.utf8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => 'English',
                    'hreflang' => 'en-US',
                    'direction' => 'ltr',
                    'flag' => 'pink',
                    'websiteTitle' => strtoupper($type) . ' default language',
                ],
                [
                    'title' => 'Spanish US',
                    'enabled' => true,
                    'base' => '/es-us/',
                    'typo3Language' => 'es',
                    'locale' => 'es_US.utf8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => strtoupper($type) . ' fallback to default',
                    'navigationTitle' => '',
                    'hreflang' => 'es-US',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => '0',
                    'flag' => 'purple',
                    'languageId' => $highestLanguageId + 1,
                ],
                [
                    'title' => 'Spanish MX',
                    'enabled' => true,
                    'base' => '/es-mx/',
                    'typo3Language' => 'es',
                    'locale' => 'es_MX.utf8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => strtoupper($type) . ' fallback to translation to default',
                    'navigationTitle' => '',
                    'hreflang' => 'es-MX',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => $highestLanguageId + 1 . ',0',
                    'flag' => 'indigo',
                    'languageId' => $highestLanguageId + 2,
                ],
                [
                    'title' => 'German DE',
                    'enabled' => true,
                    'base' => '/de-de/',
                    'typo3Language' => 'de',
                    'locale' => 'de_DE.utf8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => strtoupper($type) . ' no fallback',
                    'navigationTitle' => '',
                    'hreflang' => 'de-DE',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => '',
                    'flag' => 'teal',
                    'languageId' => $highestLanguageId + 3,
                ],
                [
                    'title' => 'German AT',
                    'enabled' => true,
                    'base' => '/de-at/',
                    'typo3Language' => 'de',
                    'locale' => 'de_AT.utf8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => strtoupper($type) . ' fallback to translation no default',
                    'navigationTitle' => '',
                    'hreflang' => 'de-AT',
                    'direction' => 'ltr',
                    'fallbackType' => $type,
                    'fallbacks' => $highestLanguageId + 3 . '',
                    'flag' => 'green',
                    'languageId' => $highestLanguageId + 4,
                ],

            ],
        ];
        $siteConfiguration->write($siteIdentifier, $configuration);
    }

    /**
     * Return array of all content elements to create
     *
     * @return array
     */
    protected function getElementContent(): array
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

    /**
     * Returns the uid of the last "top level" page (has pid 0)
     * in the page tree. This is either a positive integer or 0
     * if no page exists in the page tree at all.
     *
     * @return int
     */
    protected function getUidOfLastTopLevelPage(): int
    {
        $uid = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        try {
            $lastPage = $queryBuilder->select('uid')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
                ->orderBy('sorting', 'DESC')
                ->executeQuery()
                ->fetchOne();
        } catch (Exception $e) {
            return 0;
        }

        if ($lastPage > 0 && MathUtility::canBeInterpretedAsInteger($lastPage)) {
            $uid = (int)$lastPage;
        }
        return $uid;
    }

    /**
     * Get all page UIDs by type
     *
     * @param array|string[] $types
     * @return array
     */
    public function findUidsOfPages(array $types): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder->select('uid')
            ->from('pages');

        foreach ($types as $type) {
            if (!str_starts_with($type, 'tx_translationhandling_')) {
                continue;
            }

            $queryBuilder->orWhere(
                $queryBuilder->expr()->eq(
                    self::T3THI_FIELD,
                    $queryBuilder->createNamedParameter((string)$type)
                )
            );
        }

        $rows = $queryBuilder->orderBy('pid', 'DESC')->executeQuery()->fetchAllAssociative();
        $result = [];
        if (is_array($rows)) {
            $result = array_column($rows, 'uid');
            sort($result);
        }

        return $result;
    }

    protected function executeDataHandler(array $data = [], array $commands = []): void
    {
        if (!empty($data) || !empty($commands)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->enableLogging = false;
            $dataHandler->bypassAccessCheckForRecords = true;
            $dataHandler->bypassWorkspaceRestrictions = true;
            $dataHandler->start($data, $commands);
            if (Environment::isCli()) {
                $dataHandler->clear_cacheCmd('all');
            }

            empty($data) ?: $dataHandler->process_datamap();
            empty($commands) ?: $dataHandler->process_cmdmap();

            // Update signal only if not running in cli mode
            if (!Environment::isCli()) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }
        }
    }

    /**
     * Returns the highest language id from all sites
     *
     * @return int
     */
    public function findHighestLanguageId(): int
    {
        $lastLanguageId = 0;
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                if ($language->getLanguageId() > $lastLanguageId) {
                    $lastLanguageId = $language->getLanguageId();
                }
            }
        }
        return $lastLanguageId;
    }
}
