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
use T3thi\TranslationHandling\Event\DeleteTranslationHandlingScenarioEvent;
use T3thi\TranslationHandling\Utility\DataHandlerService;
use T3thi\TranslationHandling\Utility\Exception;
use T3thi\TranslationHandling\Utility\FileHandler;
use T3thi\TranslationHandling\Utility\RecordFinder;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

final class DeleteTranslationHandlingScenarioListener
{
    private const T3THI_FIELD = 'tx_translationhandling_identifier';

    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly SiteFinder $siteFinder,
        private readonly FileHandler $fileHandler,
        private readonly SiteWriter $siteWriter,
        private readonly LoggerInterface $logger,
        private readonly DataHandlerService $dataHandlerService
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(DeleteTranslationHandlingScenarioEvent $event): void
    {
        $type = $event->getType();
        $output = $event->getOutput();

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

        $rootUid = $this->recordFinder->findUidsOfPages([$t3thiIdentifier . '_root'], self::T3THI_FIELD);
        if (empty($rootUid)) {
            $output->writeln('page for type ' . $type . ' has already been deleted');
            return;
        }

        // Delete site configuration
        try {
            $site = $this->siteFinder->getSiteByRootPageId((int)$rootUid[0]);
            $identifier = $site->getIdentifier();
            $this->siteWriter->delete($identifier);
        } catch (SiteConfigurationWriteException $e) {
            $message = 'Site configuration can not be deleted.';
            $this->logger->error($message, ['exception' => $e]);
            throw new Exception($message, 1757323932);
        } catch (SiteNotFoundException $e) {
            // Do not throw a thing if site config does not exist
            $this->logger->info('Site configuration not found.', ['exception' => $e]);
        }

        // TODO: delete site configuration folder

        // Delete records data
        $this->dataHandlerService->executeDataHandler([], $commands);

        // Delete created files
        $this->fileHandler->deleteFalFolder('translation_handling');

        $output->writeln('page for type ' . $type . ' deleted');
    }
}
