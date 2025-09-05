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

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Manage files
 */
final class FileHandler
{
    public const T3THI_FOLDER = 'translation_handling';

    public function __construct(
        private readonly StorageRepository $storageRepository,
        private readonly RecordFinder $recordFinder,
    ) {}

    /**
     * Add files to fileadmin
     */
    public function addToFal(array $files, string $from, string $to): void
    {
        $storage = $this->storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();

        try {
            $folder->createFolder($to);
            $folder = $folder->getSubfolder($to);
            foreach ($files as $fileName) {
                $sourceLocation = GeneralUtility::getFileAbsFileName($from . $fileName);
                $storage->addFile($sourceLocation, $folder, $fileName, DuplicationBehavior::RENAME, false);
            }
        } catch (ExistingTargetFolderException $e) {
            // No op if folder exists. This code assumes file exist, too.
        }
    }

    /**
     *  Delete files from fileadmin/
     */
    public function deleteFalFolder(string $path): void
    {
        $storage = $this->storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();
        try {
            $folder = $folder->getSubfolder($path);
            $folder->delete(true);
        } catch (\InvalidArgumentException $e) {
            // No op if folder does not exist
        }
    }

    /**
     *  Get data for file references to add to existing content elements
     */
    public function getFalDataForContent(string $type, string $t3hiField): array
    {
        $files = $this->findDemoFileObjects();

        $recordData = [];
        foreach ($this->recordFinder->findTtContent($type, $t3hiField) as $content) {
            switch ($content['CType']) {
                case 'textmedia':
                    $fieldname = 'assets';
                    break;
                case 'uploads':
                    $fieldname = 'media';
                    break;
                default:
                    $fieldname = 'image';
            }

            foreach ($files as $image) {
                $newId = StringUtility::getUniqueId('NEW');
                $recordData['sys_file_reference'][$newId] = [
                    'table_local' => 'sys_file',
                    'uid_local' => $image->getUid(),
                    'uid_foreign' => $content['uid'],
                    'tablenames' => 'tt_content',
                    'fieldname' => $fieldname,
                    'pid' => $content['pid'],
                ];
            }
        }

        return $recordData;
    }

    /**
     *  Find the object representation of the demo images
     */
    protected function findDemoFileObjects(): array
    {
        $storage = $this->storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();
        $folder = $folder->getSubfolder(self::T3THI_FOLDER);
        return $folder->getFiles();
    }

    public function getFalDataForPages(string $type, string $t3hiField): array
    {
        $files = $this->findDemoFileObjects();
        $t3thiIdentifier = 'tx_translationhandling_' . $type;

        $recordData = [];
        foreach ($this->recordFinder->findUidsOfPages([
            $t3thiIdentifier . '_root',
            $t3thiIdentifier,
        ], $t3hiField) as $pageUid) {
            foreach ($files as $image) {
                $newId = StringUtility::getUniqueId('NEW');
                $recordData['sys_file_reference'][$newId] = [
                    'table_local' => 'sys_file',
                    'uid_local' => $image->getUid(),
                    'uid_foreign' => $pageUid,
                    'tablenames' => 'pages',
                    'fieldname' => 'media',
                    'pid' => $pageUid,
                ];
            }
        }

        return $recordData;
    }
}
