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
use T3thi\TranslationHandling\Event\CreateTranslationHandlingScenarioEvent;
use T3thi\TranslationHandling\Event\PageCreationEvent;
use T3thi\TranslationHandling\Utility\FileHandler;
use T3thi\TranslationHandling\Utility\RecordFinder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final class CreateTranslationHandlingScenarioListener
{
    private const T3THI_FIELD = 'tx_translationhandling_identifier';
    private DataHandler $dataHandler;

    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly FileHandler $fileHandler,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(CreateTranslationHandlingScenarioEvent $event): void
    {
        $type = $event->getType();
        $output = $event->getOutput();

        $t3thiIdentifier = 'tx_translationhandling_' . $type;
        $title = 'TYPO3 Translation Handling - ' . strtoupper($type);

        // Early return if demo data is already available for this type
        if (count($this->recordFinder->findUidsOfPages([$t3thiIdentifier . '_root'], self::T3THI_FIELD))) {
            $output->writeln('Can not create a second record tree for ' . $type);
            return;
        }

        // Add files
        $this->fileHandler->addToFal([
            'Superhero_00032_.jpg',
        ], 'EXT:translation_handling/Resources/Public/Images/', 'translation_handling');

        $this->eventDispatcher->dispatch(new PageCreationEvent($type, $output));

        $output->writeln('Scenario "' . $type . '" created.');
    }
}