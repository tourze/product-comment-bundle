<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductCommentBundle\Entity\CommentLikeLog;
use Tourze\ProductCommentBundle\Repository\CommentLikeLogRepository;

/**
 * @template-extends AbstractRepositoryTestCase<CommentLikeLog>
 * @internal
 */
#[CoversClass(CommentLikeLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class CommentLikeLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $entity = new CommentLikeLog();
        $entity->setCommentId(123);
        $entity->setMemberId(456);
        $entity->setStatus(true);

        return $entity;
    }

    protected function getRepository(): CommentLikeLogRepository
    {
        return self::getService(CommentLikeLogRepository::class);
    }

    protected function onSetUp(): void
    {
        // 测试环境设置
    }

    private function createCommentLikeLog(int $commentId, int $memberId, bool $status): CommentLikeLog
    {
        $log = new CommentLikeLog();
        $log->setCommentId($commentId);
        $log->setMemberId($memberId);
        $log->setStatus($status);

        return $log;
    }

    public function testSaveAndRetrieveCommentLikeLog(): void
    {
        $repository = $this->getRepository();

        $log = $this->createCommentLikeLog(123, 456, true);

        // 测试保存
        $repository->save($log);

        // 验证保存成功
        $this->assertEntityPersisted($log);

        // 测试检索
        $foundLog = $repository->find($log->getId());
        $this->assertNotNull($foundLog);
        $this->assertEquals(123, $foundLog->getCommentId());
        $this->assertEquals(456, $foundLog->getMemberId());
        $this->assertTrue($foundLog->isStatus());
    }

    public function testRemoveCommentLikeLog(): void
    {
        $repository = $this->getRepository();

        $log = $this->createCommentLikeLog(123, 456, true);

        $repository->save($log);
        $logId = $log->getId();

        // 验证日志存在
        $this->assertNotNull($repository->find($logId));

        // 测试删除
        $repository->remove($log);

        // 验证删除成功
        $this->assertEntityNotExists(CommentLikeLog::class, $logId);
    }

    public function testFindByCommentId(): void
    {
        $repository = $this->getRepository();

        // 为评论123创建多个点赞日志
        $log1 = $this->createCommentLikeLog(123, 456, true);
        $repository->save($log1);

        $log2 = $this->createCommentLikeLog(123, 789, false);
        $repository->save($log2);

        // 为评论456创建点赞日志
        $log3 = $this->createCommentLikeLog(456, 123, true);
        $repository->save($log3);

        // 测试按评论ID查询
        $comment123Logs = $repository->findBy(['commentId' => 123]);
        $this->assertCount(2, $comment123Logs);

        $comment456Logs = $repository->findBy(['commentId' => 456]);
        $this->assertCount(1, $comment456Logs);
    }

    public function testFindByMemberIdAndStatus(): void
    {
        $repository = $this->getRepository();

        $memberId = 9999;

        // 创建该用户的多个点赞记录
        $activeLogs = [];
        $inactiveLogs = [];

        for ($i = 1; $i <= 5; ++$i) {
            $status = $i <= 3; // 前3个为活跃状态，后2个为非活跃状态

            $log = $this->createCommentLikeLog(100 + $i, $memberId, $status);
            $repository->save($log);

            if ($status) {
                $activeLogs[] = $log;
            } else {
                $inactiveLogs[] = $log;
            }
        }

        // 测试查询该用户的活跃点赞记录
        $activeResults = $repository->findBy([
            'memberId' => $memberId,
            'status' => true,
        ]);
        $this->assertCount(3, $activeResults);

        // 测试查询该用户的非活跃点赞记录
        $inactiveResults = $repository->findBy([
            'memberId' => $memberId,
            'status' => false,
        ]);
        $this->assertCount(2, $inactiveResults);

        // 测试查询该用户的所有记录
        $allResults = $repository->findBy(['memberId' => $memberId]);
        $this->assertCount(5, $allResults);
    }

    public function testFindByCommentIdAndMemberId(): void
    {
        $repository = $this->getRepository();

        $commentId = 123;
        $memberId = 456;

        // 创建该用户对该评论的点赞记录
        $log = $this->createCommentLikeLog($commentId, $memberId, true);
        $repository->save($log);

        // 创建其他干扰数据
        $otherLog1 = $this->createCommentLikeLog($commentId, 789, true);
        $repository->save($otherLog1);

        $otherLog2 = $this->createCommentLikeLog(456, $memberId, true);
        $repository->save($otherLog2);

        // 测试精确查询
        $specificLog = $repository->findOneBy([
            'commentId' => $commentId,
            'memberId' => $memberId,
        ]);

        $this->assertNotNull($specificLog);
        $this->assertEquals($log->getId(), $specificLog->getId());
        $this->assertEquals($commentId, $specificLog->getCommentId());
        $this->assertEquals($memberId, $specificLog->getMemberId());
        $this->assertTrue($specificLog->isStatus());
    }

    public function testUpdateLikeStatus(): void
    {
        $repository = $this->getRepository();

        $log = $this->createCommentLikeLog(123, 456, true);

        $repository->save($log);

        // 验证初始状态
        $savedLog = $repository->find($log->getId());
        $this->assertNotNull($savedLog);
        $this->assertTrue($savedLog->isStatus());

        // 更新状态
        $savedLog->setStatus(false);
        $repository->save($savedLog);

        // 验证状态已更新
        $updatedLog = $repository->find($log->getId());
        $this->assertNotNull($updatedLog);
        $this->assertFalse($updatedLog->isStatus());

        // 再次更新状态
        $updatedLog->setStatus(true);
        $repository->save($updatedLog);

        // 验证状态再次更新
        $finalLog = $repository->find($log->getId());
        $this->assertNotNull($finalLog);
        $this->assertTrue($finalLog->isStatus());
    }

    public function testBatchOperations(): void
    {
        $repository = $this->getRepository();

        // 清理之前的测试数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\ProductCommentBundle\Entity\CommentLikeLog')->execute();

        $logs = [];
        for ($i = 1; $i <= 3; ++$i) {
            $log = $this->createCommentLikeLog($i, 100 + $i, 1 === $i % 2);
            $logs[] = $log;
            $repository->save($log, false); // 不立即刷新
        }

        // 手动刷新
        $repository->flush();

        // 验证所有记录都已保存
        foreach ($logs as $log) {
            $this->assertEntityPersisted($log);
        }

        // 验证状态过滤查询
        $activeLogs = $repository->findBy(['status' => true]);
        $this->assertCount(2, $activeLogs); // logs[0] 和 logs[2]

        $inactiveLogs = $repository->findBy(['status' => false]);
        $this->assertCount(1, $inactiveLogs); // logs[1]
    }

    // 补充缺失的基础测试方法

    public function testClearMethod(): void
    {
        $repository = $this->getRepository();

        $log = $this->createCommentLikeLog(123, 456, true);
        $repository->save($log, true); // 立即持久化到数据库

        // 清除实体管理器缓存
        $repository->clear();

        // 验证缓存已清除，需要重新查询数据库
        $foundLog = $repository->find($log->getId());
        $this->assertNotNull($foundLog);
        $this->assertEquals(123, $foundLog->getCommentId());

        $repository->remove($foundLog);
    }

    public function testFlushMethod(): void
    {
        $repository = $this->getRepository();

        $log = $this->createCommentLikeLog(123, 456, true);
        $repository->save($log, false); // 不立即刷新

        // 手动刷新到数据库
        $repository->flush();

        // 验证数据已刷新到数据库
        $foundLog = $repository->find($log->getId());
        $this->assertNotNull($foundLog);
        $this->assertEquals(123, $foundLog->getCommentId());

        $repository->remove($foundLog);
    }

    public function testSaveAllMethod(): void
    {
        $repository = $this->getRepository();

        $logs = [];
        for ($i = 1; $i <= 3; ++$i) {
            $log = $this->createCommentLikeLog($i, 100 + $i, 1 === $i % 2);
            $logs[] = $log;
        }

        $repository->saveAll($logs);

        // 验证所有日志都被正确保存
        foreach ($logs as $log) {
            $this->assertNotNull($log->getId());
            $repository->remove($log, false);
        }
        $repository->flush();
    }
}
