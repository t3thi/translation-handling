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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class contains helper methods to locate uids or pids of specific records
 * in the system.
 */
final class RecordFinder
{
    /**
     * Find tt_content by ctype and identifier
     */
    public function findTtContent(string $type, string $t3hiField, array $cTypes = ['textmedia', 'textpic', 'image', 'uploads']): array
    {
        $identifier = 'tx_translationhandling_' . $type;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder->select('uid', 'pid', 'CType')->from('tt_content')->where($queryBuilder->expr()->eq($t3hiField, $queryBuilder->createNamedParameter($identifier)));

        if (!empty($cTypes)) {
            $orExpression = $queryBuilder->expr()->or();
            foreach ($cTypes as $cType) {
                $orExpression = $orExpression->with($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($cType)));
            }
            $queryBuilder->andWhere((string)$orExpression);
        }

        return $queryBuilder->orderBy('uid', 'DESC')->executeQuery()->fetchAllAssociative();
    }

    /**
     * Returns the uid of the last "top level" page (has pid 0)
     * in the page tree. This is either a positive integer or 0
     * if no page exists in the page tree at all.
     *
     */
    public function getUidOfLastTopLevelPage(): int
    {
        $uid = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        try {
            $lastPage = $queryBuilder->select('uid')->from('pages')->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))->orderBy('sorting', 'DESC')->executeQuery()->fetchOne();
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
     */
    public function findUidsOfPages(array $types, string $t3hiField): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder->select('uid')->from('pages');

        foreach ($types as $type) {
            if (!str_starts_with($type, 'tx_translationhandling_')) {
                continue;
            }

            $queryBuilder->orWhere($queryBuilder->expr()->eq($t3hiField, $queryBuilder->createNamedParameter((string)$type)));
        }

        $rows = $queryBuilder->orderBy('pid', 'DESC')->executeQuery()->fetchAllAssociative();
        $result = [];
        if (is_array($rows)) {
            $result = array_column($rows, 'uid');
            sort($result);
        }

        return $result;
    }

    /**
     * Returns the highest language id from all sites
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

    /**
     * Returns the language ids for the given root page
     */
    public function findLanguageIdsByRootPage(int $rootPageId): array
    {
        $lastLanguageIds = [];
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($rootPageId);
        foreach ($site->getAllLanguages() as $language) {
            if ($language->getLanguageId() > 0) {
                $lastLanguageIds[] = $language->getLanguageId();
            }
        }

        return $lastLanguageIds;
    }
}
