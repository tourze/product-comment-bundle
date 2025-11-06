<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @template-extends AbstractRepositoryTestCase<ProductComment>
 * @internal
 */
#[CoversClass(ProductCommentRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProductCommentRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $entity = new ProductComment();
        $entity->setSpu($spu);
        $entity->setFromUser($user);
        $entity->setState(CommentStateEnum::APPROVED);
        $entity->setContent('Test comment content');

        return $entity;
    }

    protected function getRepository(): ProductCommentRepository
    {
        return self::getService(ProductCommentRepository::class);
    }

    protected function onSetUp(): void
    {
        // 测试环境设置
    }

    public function testSaveAndRetrieveProductComment(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContent('这是一条测试评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setClientIp('127.0.0.1');
        $comment->setTopicType(0);
        $comment->setIsGoods(0);
        $comment->setRateNum(0);
        $comment->setLikeNum(0);
        $comment->setIsAdmin(0);
        $comment->setVideo('');
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();

        // 测试保存
        $repository->save($comment);

        // 验证保存成功
        $this->assertEntityPersisted($comment);

        // 测试检索
        $foundComment = $repository->find($comment->getId());
        $this->assertNotNull($foundComment);
        $this->assertEquals('这是一条测试评论', $foundComment->getContent());
        $foundSpu = $foundComment->getSpu();
        $this->assertNotNull($foundSpu);
        $this->assertEquals($spu->getId(), $foundSpu->getId());
        $fromUser = $foundComment->getFromUser();
        $this->assertNotNull($fromUser, 'From user should not be null');
        $this->assertEquals($user->getUserIdentifier(), $fromUser->getUserIdentifier());
    }

    public function testRemoveProductComment(): void
    {
        $repository = $this->getRepository();

        // 创建并保存测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContent('待删除的评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setClientIp('127.0.0.1');
        $comment->setTopicType(0);
        $comment->setIsGoods(0);
        $comment->setRateNum(0);
        $comment->setLikeNum(0);
        $comment->setIsAdmin(0);
        $comment->setVideo('');
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();

        $repository->save($comment);
        $commentId = $comment->getId();

        // 验证评论存在
        $this->assertNotNull($repository->find($commentId));

        // 测试删除
        $repository->remove($comment);

        // 验证删除成功
        $this->assertEntityNotExists(ProductComment::class, $commentId);
    }

    public function testFindBySpuAndState(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu1 = $this->createTestSpu();
        $spu2 = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 为 spu1 创建已审核评论
        $approvedComment = new ProductComment();
        $approvedComment->setSpu($spu1);
        $approvedComment->setFromUser($user);
        $approvedComment->setContent('已审核的评论');
        $approvedComment->setState(CommentStateEnum::APPROVED);
        $approvedComment->setRootParentId(0);
        $approvedComment->setParentId(0);
        $approvedComment->setClientIp('127.0.0.1');
        $approvedComment->setTopicType(0);
        $approvedComment->setIsGoods(0);
        $approvedComment->setRateNum(0);
        $approvedComment->setLikeNum(0);
        $approvedComment->setIsAdmin(0);
        $approvedComment->setVideo('');
        self::getEntityManager()->persist($approvedComment);
        self::getEntityManager()->flush();
        $repository->save($approvedComment);

        // 为 spu1 创建待审核评论
        $pendingComment = new ProductComment();
        $pendingComment->setSpu($spu1);
        $pendingComment->setFromUser($user);
        $pendingComment->setContent('待审核的评论');
        $pendingComment->setState(CommentStateEnum::PENDING);
        $pendingComment->setRootParentId(0);
        $pendingComment->setParentId(0);
        $pendingComment->setClientIp('127.0.0.1');
        $pendingComment->setTopicType(0);
        $pendingComment->setIsGoods(0);
        $pendingComment->setRateNum(0);
        $pendingComment->setLikeNum(0);
        $pendingComment->setIsAdmin(0);
        $pendingComment->setVideo('');
        self::getEntityManager()->persist($pendingComment);
        self::getEntityManager()->flush();
        $repository->save($pendingComment);

        // 为 spu2 创建已审核评论
        $otherSpuComment = new ProductComment();
        $otherSpuComment->setSpu($spu2);
        $otherSpuComment->setFromUser($user);
        $otherSpuComment->setContent('其他商品的评论');
        $otherSpuComment->setState(CommentStateEnum::APPROVED);
        $otherSpuComment->setRootParentId(0);
        $otherSpuComment->setParentId(0);
        $otherSpuComment->setClientIp('127.0.0.1');
        $otherSpuComment->setTopicType(0);
        $otherSpuComment->setIsGoods(0);
        $otherSpuComment->setRateNum(0);
        $otherSpuComment->setLikeNum(0);
        $otherSpuComment->setIsAdmin(0);
        $otherSpuComment->setVideo('');
        self::getEntityManager()->persist($otherSpuComment);
        self::getEntityManager()->flush();
        $repository->save($otherSpuComment);

        // 测试按 SPU 和状态查询
        $approvedCommentsForSpu1 = $repository->findBy([
            'spu' => $spu1,
            'state' => CommentStateEnum::APPROVED,
        ]);

        $this->assertCount(1, $approvedCommentsForSpu1);
        $this->assertEquals('已审核的评论', $approvedCommentsForSpu1[0]->getContent());

        // 测试按 SPU 查询所有评论
        $allCommentsForSpu1 = $repository->findBy(['spu' => $spu1]);
        $this->assertCount(2, $allCommentsForSpu1);
    }

    public function testFindRootComments(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_1_' . uniqid());
        $user2 = $this->createNormalUser('test_user_2_' . uniqid());

        // 创建根评论
        $rootComment = new ProductComment();
        $rootComment->setSpu($spu);
        $rootComment->setFromUser($user1);
        $rootComment->setContent('根评论');
        $rootComment->setState(CommentStateEnum::APPROVED);
        $rootComment->setRootParentId(0);
        $rootComment->setParentId(0);
        $rootComment->setClientIp('127.0.0.1');
        $rootComment->setTopicType(0);
        $rootComment->setIsGoods(0);
        $rootComment->setRateNum(0);
        $rootComment->setLikeNum(0);
        $rootComment->setIsAdmin(0);
        $rootComment->setVideo('');
        self::getEntityManager()->persist($rootComment);
        self::getEntityManager()->flush();
        $repository->save($rootComment);

        // 创建回复评论
        $replyComment = new ProductComment();
        $replyComment->setSpu($spu);
        $replyComment->setFromUser($user2);
        $replyComment->setToUser($user1);
        $replyComment->setContent('回复评论');
        $replyComment->setState(CommentStateEnum::APPROVED);
        $replyComment->setRootParentId((int) $rootComment->getId());
        $replyComment->setParentId((int) $rootComment->getId());
        $replyComment->setClientIp('127.0.0.1');
        $replyComment->setTopicType(0);
        $replyComment->setIsGoods(0);
        $replyComment->setRateNum(0);
        $replyComment->setLikeNum(0);
        $replyComment->setIsAdmin(0);
        $replyComment->setVideo('');
        self::getEntityManager()->persist($replyComment);
        self::getEntityManager()->flush();
        $repository->save($replyComment);

        // 查询根评论
        $rootComments = $repository->findBy([
            'spu' => $spu,
            'rootParentId' => 0,
            'state' => CommentStateEnum::APPROVED,
        ]);

        $this->assertCount(1, $rootComments);
        $this->assertEquals('根评论', $rootComments[0]->getContent());

        // 查询回复评论
        $replyComments = $repository->findBy([
            'rootParentId' => $rootComment->getId(),
            'state' => CommentStateEnum::APPROVED,
        ]);

        $this->assertCount(1, $replyComments);
        $this->assertEquals('回复评论', $replyComments[0]->getContent());
        $this->assertEquals($user1, $replyComments[0]->getToUser());
    }

    public function testBatchSaveAndFlush(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comments = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user);
            $comment->setContent("批量评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $comments[] = $comment;
            $repository->save($comment, false); // 不立即刷新
        }

        // 手动刷新
        $repository->flush();

        // 验证所有评论都已保存
        foreach ($comments as $comment) {
            $this->assertEntityPersisted($comment);
        }

        // 验证可以查询到所有评论
        $allComments = $repository->findBy(['spu' => $spu]);
        $this->assertCount(3, $allComments);
    }

    private function createTestSpu(): Spu
    {
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setValid(true);
        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        return $spu;
    }

    // 补充缺失的基础测试方法

    public function testClearMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContent('待清除的评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setClientIp('127.0.0.1');
        $comment->setTopicType(0);
        $comment->setIsGoods(0);
        $comment->setRateNum(0);
        $comment->setLikeNum(0);
        $comment->setIsAdmin(0);
        $comment->setVideo('');
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();
        $repository->save($comment, false);

        // 清除实体管理器缓存
        $repository->clear();

        // 验证缓存已清除，需要重新查询数据库
        $foundComment = $repository->find($comment->getId());
        $this->assertNotNull($foundComment);
        $this->assertEquals('待清除的评论', $foundComment->getContent());

        $repository->remove($foundComment);
    }

    public function testFlushMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContent('测试刷新的评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setClientIp('127.0.0.1');
        $comment->setTopicType(0);
        $comment->setIsGoods(0);
        $comment->setRateNum(0);
        $comment->setLikeNum(0);
        $comment->setIsAdmin(0);
        $comment->setVideo('');
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();
        $repository->save($comment, false); // 不立即刷新

        // 手动刷新到数据库
        $repository->flush();

        // 验证数据已刷新到数据库
        $foundComment = $repository->find($comment->getId());
        $this->assertNotNull($foundComment);
        $this->assertEquals('测试刷新的评论', $foundComment->getContent());

        $repository->remove($foundComment);
    }

    public function testSaveAllMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        $comments = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user);
            $comment->setContent("批量保存评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $comments[] = $comment;
        }

        $repository->saveAll($comments);

        foreach ($comments as $comment) {
            $this->assertNotNull($comment->getId());
            $repository->remove($comment, false);
        }
        $repository->flush();
    }

    public function testFindByAssociationRelations(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());

        // 创建有关联关系的评论（有toUser）
        $commentWithToUser = new ProductComment();
        $commentWithToUser->setSpu($spu);
        $commentWithToUser->setFromUser($user1);
        $commentWithToUser->setToUser($user2);
        $commentWithToUser->setContent('带关联用户的评论');
        $commentWithToUser->setState(CommentStateEnum::APPROVED);
        $commentWithToUser->setRootParentId(0);
        $commentWithToUser->setParentId(0);
        $commentWithToUser->setClientIp('127.0.0.1');
        $commentWithToUser->setTopicType(0);
        $commentWithToUser->setIsGoods(0);
        $commentWithToUser->setRateNum(0);
        $commentWithToUser->setLikeNum(0);
        $commentWithToUser->setIsAdmin(0);
        $commentWithToUser->setVideo('');
        self::getEntityManager()->persist($commentWithToUser);
        self::getEntityManager()->flush();
        $repository->save($commentWithToUser);

        // 创建无关联关系的评论（无toUser）
        $commentWithoutToUser = new ProductComment();
        $commentWithoutToUser->setSpu($spu);
        $commentWithoutToUser->setFromUser($user1);
        $commentWithoutToUser->setToUser(null);
        $commentWithoutToUser->setContent('无关联用户的评论');
        $commentWithoutToUser->setState(CommentStateEnum::APPROVED);
        $commentWithoutToUser->setRootParentId(0);
        $commentWithoutToUser->setParentId(0);
        $commentWithoutToUser->setClientIp('127.0.0.1');
        $commentWithoutToUser->setTopicType(0);
        $commentWithoutToUser->setIsGoods(0);
        $commentWithoutToUser->setRateNum(0);
        $commentWithoutToUser->setLikeNum(0);
        $commentWithoutToUser->setIsAdmin(0);
        $commentWithoutToUser->setVideo('');
        self::getEntityManager()->persist($commentWithoutToUser);
        self::getEntityManager()->flush();
        $repository->save($commentWithoutToUser);

        // 测试按关联用户查询
        $commentsWithSpecificToUser = $repository->findBy(['toUser' => $user2]);
        $this->assertCount(1, $commentsWithSpecificToUser);
        $this->assertEquals('带关联用户的评论', $commentsWithSpecificToUser[0]->getContent());

        // 测试按SPU和关联用户查询
        $commentsWithSpuAndToUser = $repository->findBy([
            'spu' => $spu,
            'toUser' => $user2,
        ]);
        $this->assertCount(1, $commentsWithSpuAndToUser);

        // 测试按来源用户查询
        $commentsFromUser1 = $repository->findBy(['fromUser' => $user1]);
        $this->assertCount(2, $commentsFromUser1);

        $repository->remove($commentWithToUser, false);
        $repository->remove($commentWithoutToUser, false);
        $repository->flush();
    }

    public function testFindByNullableFields(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建有内容的评论
        $commentWithContent = new ProductComment();
        $commentWithContent->setSpu($spu);
        $commentWithContent->setFromUser($user);
        $commentWithContent->setContent('有内容的评论');
        $commentWithContent->setState(CommentStateEnum::APPROVED);
        $commentWithContent->setRootParentId(0);
        $commentWithContent->setParentId(0);
        $commentWithContent->setClientIp('127.0.0.1');
        $commentWithContent->setTopicType(0);
        $commentWithContent->setIsGoods(0);
        $commentWithContent->setRateNum(0);
        $commentWithContent->setLikeNum(0);
        $commentWithContent->setIsAdmin(0);
        $commentWithContent->setVideo('');
        self::getEntityManager()->persist($commentWithContent);
        self::getEntityManager()->flush();
        $repository->save($commentWithContent);

        // 创建无内容的评论（可空字段为null）
        $commentWithoutContent = new ProductComment();
        $commentWithoutContent->setSpu($spu);
        $commentWithoutContent->setFromUser($user);
        $commentWithoutContent->setContent(null);
        $commentWithoutContent->setState(CommentStateEnum::APPROVED);
        $commentWithoutContent->setRootParentId(0);
        $commentWithoutContent->setParentId(0);
        $commentWithoutContent->setClientIp('127.0.0.1');
        $commentWithoutContent->setTopicType(0);
        $commentWithoutContent->setIsGoods(0);
        $commentWithoutContent->setRateNum(0);
        $commentWithoutContent->setLikeNum(0);
        $commentWithoutContent->setIsAdmin(0);
        $commentWithoutContent->setVideo('');
        self::getEntityManager()->persist($commentWithoutContent);
        self::getEntityManager()->flush();
        $repository->save($commentWithoutContent);

        // 创建有toUser的评论
        $commentWithToUser = new ProductComment();
        $commentWithToUser->setSpu($spu);
        $commentWithToUser->setFromUser($user);
        $commentWithToUser->setToUser($user);
        $commentWithToUser->setContent('有被回复用户的评论');
        $commentWithToUser->setState(CommentStateEnum::APPROVED);
        $commentWithToUser->setRootParentId(0);
        $commentWithToUser->setParentId(0);
        $commentWithToUser->setClientIp('127.0.0.1');
        $commentWithToUser->setTopicType(0);
        $commentWithToUser->setIsGoods(0);
        $commentWithToUser->setRateNum(0);
        $commentWithToUser->setLikeNum(0);
        $commentWithToUser->setIsAdmin(0);
        $commentWithToUser->setVideo('');
        self::getEntityManager()->persist($commentWithToUser);
        self::getEntityManager()->flush();
        $repository->save($commentWithToUser);

        // 创建无toUser的评论
        $commentWithoutToUser = new ProductComment();
        $commentWithoutToUser->setSpu($spu);
        $commentWithoutToUser->setFromUser($user);
        $commentWithoutToUser->setToUser(null);
        $commentWithoutToUser->setContent('无被回复用户的评论');
        $commentWithoutToUser->setState(CommentStateEnum::APPROVED);
        $commentWithoutToUser->setRootParentId(0);
        $commentWithoutToUser->setParentId(0);
        $commentWithoutToUser->setClientIp('127.0.0.1');
        $commentWithoutToUser->setTopicType(0);
        $commentWithoutToUser->setIsGoods(0);
        $commentWithoutToUser->setRateNum(0);
        $commentWithoutToUser->setLikeNum(0);
        $commentWithoutToUser->setIsAdmin(0);
        $commentWithoutToUser->setVideo('');
        self::getEntityManager()->persist($commentWithoutToUser);
        self::getEntityManager()->flush();
        $repository->save($commentWithoutToUser);

        // 使用QueryBuilder查询content为null的评论
        $qb = $repository->createQueryBuilder('c');
        $qb->where('c.content IS NULL')
            ->andWhere('c.spu = :spu')
            ->setParameter('spu', $spu)
        ;
        /** @var ProductComment[] $nullContentComments */
        $nullContentComments = $qb->getQuery()->getResult();
        $this->assertCount(1, $nullContentComments);

        // 使用QueryBuilder查询toUser为null的评论
        $qb2 = $repository->createQueryBuilder('c');
        $qb2->where('c.toUser IS NULL')
            ->andWhere('c.spu = :spu')
            ->setParameter('spu', $spu)
        ;
        /** @var ProductComment[] $nullToUserComments */
        $nullToUserComments = $qb2->getQuery()->getResult();
        $this->assertCount(3, $nullToUserComments); // commentWithContent, commentWithoutContent, commentWithoutToUser

        // 使用QueryBuilder查询toUser不为null的评论
        $qb3 = $repository->createQueryBuilder('c');
        $qb3->where('c.toUser IS NOT NULL')
            ->andWhere('c.spu = :spu')
            ->setParameter('spu', $spu)
        ;
        /** @var ProductComment[] $notNullToUserComments */
        $notNullToUserComments = $qb3->getQuery()->getResult();
        $this->assertCount(1, $notNullToUserComments);

        $repository->remove($commentWithContent, false);
        $repository->remove($commentWithoutContent, false);
        $repository->remove($commentWithToUser, false);
        $repository->remove($commentWithoutToUser, false);
        $repository->flush();
    }

    public function testCountQueriesWithCriteria(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建不同状态的评论
        $approvedComment = new ProductComment();
        $approvedComment->setSpu($spu);
        $approvedComment->setFromUser($user);
        $approvedComment->setContent('已审核评论');
        $approvedComment->setState(CommentStateEnum::APPROVED);
        $approvedComment->setRootParentId(0);
        $approvedComment->setParentId(0);
        $approvedComment->setClientIp('127.0.0.1');
        $approvedComment->setTopicType(0);
        $approvedComment->setIsGoods(0);
        $approvedComment->setRateNum(0);
        $approvedComment->setLikeNum(0);
        $approvedComment->setIsAdmin(0);
        $approvedComment->setVideo('');
        self::getEntityManager()->persist($approvedComment);
        self::getEntityManager()->flush();
        $repository->save($approvedComment);

        $pendingComment = new ProductComment();
        $pendingComment->setSpu($spu);
        $pendingComment->setFromUser($user);
        $pendingComment->setContent('待审核评论');
        $pendingComment->setState(CommentStateEnum::PENDING);
        $pendingComment->setRootParentId(0);
        $pendingComment->setParentId(0);
        $pendingComment->setClientIp('127.0.0.1');
        $pendingComment->setTopicType(0);
        $pendingComment->setIsGoods(0);
        $pendingComment->setRateNum(0);
        $pendingComment->setLikeNum(0);
        $pendingComment->setIsAdmin(0);
        $pendingComment->setVideo('');
        self::getEntityManager()->persist($pendingComment);
        self::getEntityManager()->flush();
        $repository->save($pendingComment);

        $rejectedComment = new ProductComment();
        $rejectedComment->setSpu($spu);
        $rejectedComment->setFromUser($user);
        $rejectedComment->setContent('被拒绝评论');
        $rejectedComment->setState(CommentStateEnum::REJECTED);
        $rejectedComment->setRootParentId(0);
        $rejectedComment->setParentId(0);
        $rejectedComment->setClientIp('127.0.0.1');
        $rejectedComment->setTopicType(0);
        $rejectedComment->setIsGoods(0);
        $rejectedComment->setRateNum(0);
        $rejectedComment->setLikeNum(0);
        $rejectedComment->setIsAdmin(0);
        $rejectedComment->setVideo('');
        self::getEntityManager()->persist($rejectedComment);
        self::getEntityManager()->flush();
        $repository->save($rejectedComment);

        // 测试按状态统计
        $approvedCount = $repository->count(['state' => CommentStateEnum::APPROVED]);
        $this->assertGreaterThanOrEqual(1, $approvedCount);

        $pendingCount = $repository->count(['state' => CommentStateEnum::PENDING]);
        $this->assertGreaterThanOrEqual(1, $pendingCount);

        $rejectedCount = $repository->count(['state' => CommentStateEnum::REJECTED]);
        $this->assertGreaterThanOrEqual(1, $rejectedCount);

        // 测试按SPU统计
        $spuCount = $repository->count(['spu' => $spu]);
        $this->assertGreaterThanOrEqual(3, $spuCount);

        // 测试组合条件统计
        $spuApprovedCount = $repository->count([
            'spu' => $spu,
            'state' => CommentStateEnum::APPROVED,
        ]);
        $this->assertGreaterThanOrEqual(1, $spuApprovedCount);

        $repository->remove($approvedComment, false);
        $repository->remove($pendingComment, false);
        $repository->remove($rejectedComment, false);
        $repository->flush();
    }

    public function testFindOneBySpuShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu1 = $this->createTestSpu();
        $spu2 = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建针对不同SPU的评论
        $comment1 = new ProductComment();
        $comment1->setSpu($spu1);
        $comment1->setFromUser($user);
        $comment1->setContent('第一个商品的评论');
        $comment1->setState(CommentStateEnum::APPROVED);
        $comment1->setRootParentId(0);
        $comment1->setParentId(0);
        $comment1->setClientIp('127.0.0.1');
        $comment1->setTopicType(0);
        $comment1->setIsGoods(0);
        $comment1->setRateNum(0);
        $comment1->setLikeNum(0);
        $comment1->setIsAdmin(0);
        $comment1->setVideo('');
        self::getEntityManager()->persist($comment1);
        self::getEntityManager()->flush();
        $repository->save($comment1);

        $comment2 = new ProductComment();
        $comment2->setSpu($spu2);
        $comment2->setFromUser($user);
        $comment2->setContent('第二个商品的评论');
        $comment2->setState(CommentStateEnum::APPROVED);
        $comment2->setRootParentId(0);
        $comment2->setParentId(0);
        $comment2->setClientIp('127.0.0.1');
        $comment2->setTopicType(0);
        $comment2->setIsGoods(0);
        $comment2->setRateNum(0);
        $comment2->setLikeNum(0);
        $comment2->setIsAdmin(0);
        $comment2->setVideo('');
        self::getEntityManager()->persist($comment2);
        self::getEntityManager()->flush();
        $repository->save($comment2);

        // 测试按SPU查询评论
        $result = $repository->findOneBy(['spu' => $spu1]);
        $this->assertNotNull($result);
        $this->assertEquals($comment1->getId(), $result->getId());
        $this->assertEquals('第一个商品的评论', $result->getContent());

        $repository->remove($comment1, false);
        $repository->remove($comment2, false);
        $repository->flush();
    }

    public function testFindByFromUserShouldReturnAllMatchingEntities(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());

        // 创建用户1的多个评论
        $user1Comments = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user1);
            $comment->setContent("用户1的评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $user1Comments[] = $comment;
        }

        // 创建用户2的评论
        $user2Comment = new ProductComment();
        $user2Comment->setSpu($spu);
        $user2Comment->setFromUser($user2);
        $user2Comment->setContent('用户2的评论');
        $user2Comment->setState(CommentStateEnum::APPROVED);
        $user2Comment->setRootParentId(0);
        $user2Comment->setParentId(0);
        $user2Comment->setClientIp('127.0.0.1');
        $user2Comment->setTopicType(0);
        $user2Comment->setIsGoods(0);
        $user2Comment->setRateNum(0);
        $user2Comment->setLikeNum(0);
        $user2Comment->setIsAdmin(0);
        $user2Comment->setVideo('');
        self::getEntityManager()->persist($user2Comment);
        self::getEntityManager()->flush();
        $repository->save($user2Comment, false);
        $repository->flush();

        // 按用户查询评论
        $results = $repository->findBy(['fromUser' => $user1]);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertEquals($user1, $result->getFromUser());
            $content = $result->getContent();
            $this->assertNotNull($content);
            $this->assertStringContainsString('用户1的评论', $content);
        }

        foreach ($user1Comments as $comment) {
            $repository->remove($comment, false);
        }
        $repository->remove($user2Comment, false);
        $repository->flush();
    }

    public function testCountByAssociationSpuShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();
        $spu1 = $this->createTestSpu();
        $spu2 = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建4个属于spu1的评论
        $spu1Comments = [];
        for ($i = 1; $i <= 4; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu1);
            $comment->setFromUser($user);
            $comment->setContent("SPU1评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $spu1Comments[] = $comment;
        }

        // 创建2个属于spu2的评论
        $spu2Comments = [];
        for ($i = 1; $i <= 2; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu2);
            $comment->setFromUser($user);
            $comment->setContent("SPU2评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $spu2Comments[] = $comment;
        }
        $repository->flush();

        $count = $repository->count(['spu' => $spu1]);
        $this->assertSame(4, $count);

        foreach ($spu1Comments as $comment) {
            $repository->remove($comment, false);
        }
        foreach ($spu2Comments as $comment) {
            $repository->remove($comment, false);
        }
        $repository->flush();
    }

    public function testCountByAssociationFromUserShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());

        // 创建user1的3个评论
        $user1Comments = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user1);
            $comment->setContent("用户1评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $user1Comments[] = $comment;
        }

        // 创建user2的2个评论
        $user2Comments = [];
        for ($i = 1; $i <= 2; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user2);
            $comment->setContent("用户2评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $user2Comments[] = $comment;
        }
        $repository->flush();

        $count = $repository->count(['fromUser' => $user1]);
        $this->assertSame(3, $count);

        foreach ($user1Comments as $comment) {
            $repository->remove($comment, false);
        }
        foreach ($user2Comments as $comment) {
            $repository->remove($comment, false);
        }
        $repository->flush();
    }

    public function testCountByAssociationToUserShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());
        $user3 = $this->createNormalUser('test_user_' . uniqid());

        // 创建3个被回复给user1的评论
        $toUser1Comments = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user2);
            $comment->setToUser($user1);
            $comment->setContent("回复给用户1的评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $toUser1Comments[] = $comment;
        }

        // 创建2个被回复给user2的评论
        $toUser2Comments = [];
        for ($i = 1; $i <= 2; ++$i) {
            $comment = new ProductComment();
            $comment->setSpu($spu);
            $comment->setFromUser($user3);
            $comment->setToUser($user2);
            $comment->setContent("回复给用户2的评论 {$i}");
            $comment->setState(CommentStateEnum::APPROVED);
            $comment->setRootParentId(0);
            $comment->setParentId(0);
            $comment->setClientIp('127.0.0.1');
            $comment->setTopicType(0);
            $comment->setIsGoods(0);
            $comment->setRateNum(0);
            $comment->setLikeNum(0);
            $comment->setIsAdmin(0);
            $comment->setVideo('');
            self::getEntityManager()->persist($comment);
            self::getEntityManager()->flush();
            $repository->save($comment, false);
            $toUser2Comments[] = $comment;
        }
        $repository->flush();

        $count = $repository->count(['toUser' => $user1]);
        $this->assertSame(3, $count);

        foreach ($toUser1Comments as $comment) {
            $repository->remove($comment, false);
        }
        foreach ($toUser2Comments as $comment) {
            $repository->remove($comment, false);
        }
        $repository->flush();
    }

    public function testFindOneByAssociationToUserShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());

        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user2);
        $comment->setToUser($user1);
        $comment->setContent('回复给用户1的评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setClientIp('127.0.0.1');
        $comment->setTopicType(0);
        $comment->setIsGoods(0);
        $comment->setRateNum(0);
        $comment->setLikeNum(0);
        $comment->setIsAdmin(0);
        $comment->setVideo('');
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();
        $repository->save($comment);

        $result = $repository->findOneBy(['toUser' => $user1]);
        $this->assertNotNull($result);
        $this->assertEquals($comment->getId(), $result->getId());
        $this->assertEquals($user1, $result->getToUser());

        $repository->remove($comment);
    }
}
