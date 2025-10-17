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

use Psr\Log\LoggerInterface;
use T3thi\TranslationHandling\Event\LocalizationEvent;
use T3thi\TranslationHandling\Utility\DataHandlerService;
use T3thi\TranslationHandling\Utility\Exception;
use T3thi\TranslationHandling\Utility\RecordFinder;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class LocalizationListener
{
    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly LoggerInterface $logger,
        private readonly DataHandlerService $dataHandlerService
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(LocalizationEvent $event): void
    {
        $rootPageUid = $event->getRootPageUid();
        $pageTreeData = $event->getPageTreeData();
        $output = $event->getOutput();

        $languageIds = $this->recordFinder->findLanguageIdsByRootPage($rootPageUid);
        if (empty($languageIds)) {
            $message = 'ERROR - no language uids found';
            $this->logger->error($message);
            throw new Exception($message, 1704469009);
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
                $this->dataHandlerService->executeDataHandler([], $commands);

                // Localize content for page to all languages
                $contentElements = $page['contentElements'] ?? [];
                $i = 0;
                foreach ($contentElements as $contentElement) {
                    // Don't translate if language is listed in excludeLanguages
                    $excludeLanguages = $contentElement['config']['excludeLanguages'] ?? [];
                    if (!is_array($excludeLanguages)) {
                        $message = 'type error: excludeLanguages for content must be array';
                        $this->logger->error($message);
                        throw new Exception($message, 1704540645);
                    }

                    if (in_array($languageId, $excludeLanguages, true)) {
                        $i++;
                        continue;
                    }

                    // Mixed deterministic: even position => connected (locate), odd => free (copy)
                    if ($translationMode === 'mixed') {
                        $elementMode = ($i % 2 === 0) ? 'connected' : 'free';
                    } else {
                        $elementMode = $translationMode;
                    }

                    $this->generateTranslatedRecords(
                        'tt_content',
                        $contentElement['uid'],
                        $languageId,
                        $elementMode
                    );
                    $i++;
                }
            }
        }

        $output->writeln('Localization completed for scenario: ' . $event->getType());
    }

    /**
     * @throws Exception
     */
    private function generateTranslatedRecords(
        string $tableName,
        int $uid,
        int $languageId,
        string $translationMode
    ): void {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            $message = 'No schema for table ' . $tableName;
            $this->logger->error($message);
            throw new Exception($message, 1757323932);
        }

        try {
            $schema = $this->tcaSchemaFactory->get($tableName);
        } catch (UndefinedSchemaException $e) {
            $message = 'Could not get schema for table ' . $tableName;
            $this->logger->error($message, ['exception' => $e]);
            throw new Exception($message, 1757323932, $e);
        }
        if (!$schema->hasCapability(TcaSchemaCapability::Language)) {
            $message = 'Table ' . $tableName . ' lacks Language capability';
            $this->logger->error($message);
            throw new Exception($message, 1757323932);
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
            default:
                $message = 'Unknown translation mode. ' . $translationMode;
                $this->logger->error($message);
                throw new Exception($message, 1704469009);
        }
    }

    /**
     * @throws Exception
     */
    private function localizeRecord(
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
        $this->dataHandlerService->executeDataHandler([], $commandMap);
    }
}
