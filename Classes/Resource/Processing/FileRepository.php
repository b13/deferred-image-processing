<?php

namespace WEBcoast\DeferredImageProcessing\Resource\Processing;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WEBcoast\DeferredImageProcessing\Utility\PathUtility;

class FileRepository
{
    const TABLE = 'tx_deferredimageprocessing_file';

    public static function hasProcessingInstructions(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid())),
                $queryBuilder->expr()->eq('source_file', $queryBuilder->createNamedParameter($task->getSourceFile()->getUid())),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($task->getType())),
                $queryBuilder->expr()->eq('task_name', $queryBuilder->createNamedParameter($task->getName())),
                $queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($task->getConfigurationChecksum()))
            );

        return $queryBuilder->executeQuery()->fetchOne() > 0;
    }

    public static function setProcessingInstructions(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->insert(self::TABLE)
            ->values([
                'storage' => $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid() ,ParameterType::INTEGER),
                'public_url' => $queryBuilder->createNamedParameter(PathUtility::stripLeadingSlash($task->getTargetFile()->getPublicUrl())),
                'source_file' => $queryBuilder->createNamedParameter($task->getSourceFile()->getUid(), ParameterType::INTEGER),
                'task_type' => $queryBuilder->createNamedParameter($task->getType()),
                'task_name' => $queryBuilder->createNamedParameter($task->getName()),
                'configuration' => $queryBuilder->createNamedParameter(serialize($task->getConfiguration())),
                'checksum' => $queryBuilder->createNamedParameter($task->getConfigurationChecksum())
            ], false);

        return $queryBuilder->executeStatement();
    }

    public static function getProcessingInstructions(int $maxResults = 100)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->select('*')
            ->from(self::TABLE)
            ->setMaxResults($maxResults);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public static function getProcessingInstructionsByUrl($url)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('public_url', $queryBuilder->createNamedParameter(PathUtility::stripLeadingSlash($url))));

        return $queryBuilder->executeQuery()->fetchAssociative();
    }

    public static function updatePublicUrl(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->update(self::TABLE)
            ->set('public_url', PathUtility::stripLeadingSlash($task->getTargetFile()->getPublicUrl()))
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid())),
                $queryBuilder->expr()->eq('source_file', $queryBuilder->createNamedParameter($task->getSourceFile()->getUid())),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($task->getType())),
                $queryBuilder->expr()->eq('task_name', $queryBuilder->createNamedParameter($task->getName())),
                $queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($task->getConfigurationChecksum()))
            );
        $queryBuilder->executeStatement();
    }

    public static function deleteProcessingInstructions($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->delete(self::TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)));

        return $queryBuilder->executeStatement();
    }
}
