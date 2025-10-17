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

namespace T3thi\TranslationHandling\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerService
{
    private DataHandler $dataHandler;

    /**
     * @throws Exception
     */
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

            // Cancel if errors have occurred in the data handler
            if (!empty($this->dataHandler->errorLog)) {
                $message = 'DataHandler error(s): ' . implode($this->dataHandler->errorLog);
                throw new Exception($message, 1757323932);
            }
        }
    }

    public function getSubstNEWwithIDs(): array
    {
        return $this->dataHandler->substNEWwithIDs;
    }
}
