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

namespace T3thi\TranslationHandling\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use T3thi\TranslationHandling\Event\ContentCreationEvent;
use T3thi\TranslationHandling\Event\PageCreationEvent;
use T3thi\TranslationHandling\Event\SiteConfigurationCreationEvent;
use T3thi\TranslationHandling\Utility\DataHandlerService;
use T3thi\TranslationHandling\Utility\RecordFinder;
use TYPO3\CMS\Core\Utility\StringUtility;

final class PageCreationListener
{
    private const T3THI_FIELD = 'tx_translationhandling_identifier';

    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataHandlerService $dataHandlerService
    ) {}

    public function __invoke(PageCreationEvent $event): void
    {
        $type = $event->getType();
        $output = $event->getOutput();

        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $title = 'TYPO3 Translation Handling - ' . strtoupper($type);

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

        $this->dataHandlerService->executeDataHandler($data);

        // Keep uids of created pages for translation, adding subpages and content
        $rootPageUid = $this->dataHandlerService->getSubstNEWwithIDs()[$newIdOfEntryPage] ?? $newIdOfEntryPage;
        $storagePageUid = $this->dataHandlerService->getSubstNEWwithIDs()[$newIdOfRecordsStorage] ?? $newIdOfRecordsStorage;

        $this->eventDispatcher->dispatch(new SiteConfigurationCreationEvent($type, $rootPageUid, $output));

        $pageTreeData = [
            'root' => [
                'uid' => $rootPageUid,
            ],
            'storage' => [
                'uid' => $storagePageUid,
            ],
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
            $this->dataHandlerService->executeDataHandler($pageData);
            $pageUid = $this->dataHandlerService->getSubstNEWwithIDs()[$newIdOfPage] ?? $newIdOfPage;
            $pageTreeData['pages-' . $pageUid] = [
                'uid' => $pageUid,
                'mode' => $mode,
            ];
        }

        $this->eventDispatcher->dispatch(new ContentCreationEvent($type, $rootPageUid, $pageTreeData, $output));

        $output->writeln('Pages created for scenario: ' . $type);
    }
}
