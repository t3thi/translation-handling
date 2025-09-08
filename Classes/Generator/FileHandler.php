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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
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
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Add files to fileadmin
     */
    public function addToFal(array $files, string $from, string $to): void
    {
        $storage = $this->storageRepository->findByUid(1);
        if (!$storage instanceof ResourceStorage) {
            $this->logger->error('FAL storage with UID 1 not found or invalid.');
            return;
        }
        if (!$storage->isOnline()) {
            $this->logger->error('FAL storage with UID 1 is offline.');
            return;
        }
        $folder = $storage->getRootLevelFolder();

        try {
            $folder->createFolder($to);
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->logger->error('No write permission for target folder.', ['exception' => $e]);
            return;
        } catch (ExistingTargetFolderException $e) {
            $this->logger->info('No operation, as folder already exists. This code assumes that the file also exists.', ['exception' => $e]);
            return;
        }

        try {
            $folder = $folder->getSubfolder($to);
        } catch (FolderDoesNotExistException $e) {
            $this->logger->error('Target folder does not exist.', ['exception' => $e]);
            return;
        }

        foreach ($files as $fileName) {
            $sourceLocation = GeneralUtility::getFileAbsFileName($from . $fileName);

            try {
                $storage->addFile($sourceLocation, $folder, $fileName, DuplicationBehavior::RENAME, false);
            } catch (ExistingTargetFileNameException $e) {
                $this->logger->warning('File with the same name already exists.', ['exception' => $e]);
                return;
            }
        }
    }

    /**
     * Delete folder from fileadmin/
     */
    public function deleteFalFolder(string $path): void
    {
        $storage = $this->storageRepository->findByUid(1);
        if (!$storage instanceof ResourceStorage) {
            $this->logger->error('FAL storage with UID 1 not found or invalid.');
            return;
        }
        if (!$storage->isOnline()) {
            $this->logger->error('FAL storage with UID 1 is offline.');
            return;
        }

        $path = ltrim($path, '/');
        $root = $storage->getRootLevelFolder();

        try {
            $folder = $root->getSubfolder($path);
        } catch (FolderDoesNotExistException $e) {
            $this->logger->info('The folder does not exist or has already been deleted.', ['exception' => $e]);
            return;
        }

        $folder->delete();
    }

    /**
     * Get data for file references to add to existing content elements
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
     * Find the object representation of the demo images.
     *
     * @return array<int, FileInterface>
     */
    protected function findDemoFileObjects(): array
    {
        $storage = $this->storageRepository->findByUid(1);
        if (!$storage instanceof ResourceStorage) {
            $this->logger->error('FAL storage with UID 1 not found or invalid.');
            return [];
        }
        if (!$storage->isOnline()) {
            $this->logger->error('FAL storage with UID 1 is offline.');
            return [];
        }

        $root = $storage->getRootLevelFolder();

        try {
            $folder = $root->getSubfolder(self::T3THI_FOLDER);
        } catch (FolderDoesNotExistException $e) {
            $this->logger->info('Folder does not exist.', ['exception' => $e]);
            return [];
        }

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
