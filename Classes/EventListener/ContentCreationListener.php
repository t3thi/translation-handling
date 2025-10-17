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
use T3thi\TranslationHandling\Event\LocalizationEvent;
use T3thi\TranslationHandling\Utility\DataHandlerService;
use TYPO3\CMS\Core\Utility\StringUtility;

final class ContentCreationListener
{
    private const T3THI_FIELD = 'tx_translationhandling_identifier';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DataHandlerService $dataHandlerService
    ) {}

    public function __invoke(ContentCreationEvent $event): void
    {
        $type = $event->getType();
        $rootPageUid = $event->getRootPageUid();
        $pageTreeData = $event->getPageTreeData();
        $output = $event->getOutput();

        $t3thiIdentifier = 'tx_translationhandling_' . $type;

        // Add content to root page
        $rootContentElements = $this->addContentToPage(
            $this->getRootContent($type),
            $rootPageUid,
            $t3thiIdentifier
        );

        // Build page tree data array for translation
        $pageTreeData['root']['contentElements'] = $rootContentElements;

        // Add content to subpages
        foreach ($pageTreeData as &$page) {
            if (!isset($page['uid'])) {
                continue;
            }
            $contentElements = $this->addContentToPage($this->getContent(), $page['uid'], $t3thiIdentifier);
            $page['contentElements'] = $contentElements;
        }

        $this->eventDispatcher->dispatch(new LocalizationEvent($type, $rootPageUid, $pageTreeData, $output));

        $output->writeln('Content created for scenario: ' . $type);
    }

    private function addContentToPage(array $contentElements, int $pageUid, string $t3thiIdentifier): array
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
                $this->dataHandlerService->executeDataHandler($contentData);

                // Keep uids and config of created content elements
                $createdContentElements[] = [
                    'uid' => $this->dataHandlerService->getSubstNEWwithIDs()[$newIdOfContent] ?? $newIdOfContent,
                    'config' => $content['config'] ?? [],
                ];
            }
        }

        return $createdContentElements;
    }

    private function cleanContentData(array $content): array
    {
        if (isset($content['config'])) {
            unset($content['config']);
        }

        return $content;
    }

    private function getRootContent(string $type): array
    {
        return [
            'textmedia' => [
                [
                    'header' => 'Welcome to the translation handling demo for type ' . $type,
                    'bodytext' => 'This is a demo of the translation handling functionality in TYPO3.',
                ],
            ],
        ];
    }

    private function getContent(): array
    {
        return [
            'textmedia' => [
                [
                    'header' => 'This is a textmedia element',
                    'bodytext' => 'This is the bodytext of the textmedia element.',
                ],
            ],
            'text' => [
                [
                    'header' => 'This is a text element',
                    'bodytext' => 'This is the bodytext of the text element.',
                ],
            ],
        ];
    }
}
