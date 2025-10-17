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
use T3thi\TranslationHandling\Event\SiteConfigurationCreationEvent;
use T3thi\TranslationHandling\Utility\Exception;
use T3thi\TranslationHandling\Utility\RecordFinder;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

final class SiteConfigurationCreationListener
{
    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly SiteFinder $siteFinder,
        private readonly SiteWriter $siteWriter,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(SiteConfigurationCreationEvent $event): void
    {
        $type = $event->getType();
        $rootPageUid = $event->getRootPageUid();
        $output = $event->getOutput();

        $title = 'TYPO3 Translation Handling - ' . strtoupper($type);

        // When the DataHandler created the page tree, a default site configuration has been added. Fetch,  rename, update.
        $siteIdentifier = 'translation-handling-' . $type . '-' . $rootPageUid;
        try {
            $site = $this->siteFinder->getSiteByRootPageId($rootPageUid);
            $this->siteWriter->rename($site->getIdentifier(), $siteIdentifier);
        } catch (SiteConfigurationWriteException $e) {
            $message = 'Site configuration can not be renamed.';
            $this->logger->error($message, ['exception' => $e]);
            throw new Exception($message, 1757323932);
        } catch (SiteNotFoundException $e) {
            $this->logger->info('Do not rename, just write a new one', ['exception' => $e]);
        }
        $highestLanguageId = $this->recordFinder->findHighestLanguageId();
        $configuration = [
            'base' => '/' . $siteIdentifier,
            'rootPageId' => $rootPageUid,
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
            ],
        ];
        try {
            $this->siteWriter->write($siteIdentifier, $configuration);
        } catch (SiteConfigurationWriteException $e) {
            $message = 'Site configuration cannot be written.';
            $this->logger->error($message, ['exception' => $e]);
            throw new Exception($message, 1757323932);
        }

        $output->writeln('Site configuration created for scenario: ' . $type);
    }
}
