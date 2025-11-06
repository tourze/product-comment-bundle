<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Repository\ProductCommentLikeRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @template-extends AbstractRepositoryTestCase<ProductCommentLike>
 * @internal
 */
#[CoversClass(ProductCommentLikeRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProductCommentLikeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $user = $this->createNormalUser('test_user_' . uniqid());
        $spu = $this->createTestSpu();
        $comment = $this->createTestComment($spu, $user);

        $entity = new ProductCommentLike();
        $entity->setUser($user);
        $entity->setProductComment($comment);
        $entity->setStatus(1);

        return $entity;
    }

    protected function getRepository(): ProductCommentLikeRepository
    {
        return self::getService(ProductCommentLikeRepository::class);
    }

    protected function onSetUp(): void
    {
        // 测试环境设置
        // 注意：此包使用 .env.test.local 配置文件数据库以修复连接测试问题
    }

    public function testSaveAndRetrieveProductCommentLike(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);

        // 测试保存
        $repository->save($like);

        // 验证保存成功
        $this->assertEntityPersisted($like);

        // 测试检索
        $foundLike = $repository->find($like->getId());
        $this->assertNotNull($foundLike);
        $this->assertEquals(1, $foundLike->getStatus());
        $foundUser = $foundLike->getUser();
        $this->assertNotNull($foundUser, 'User should not be null');
        $this->assertEquals($user->getUserIdentifier(), $foundUser->getUserIdentifier());
        $foundComment = $foundLike->getProductComment();
        $this->assertNotNull($foundComment);
        $this->assertEquals($comment->getId(), $foundComment->getId());
    }

    public function testRemoveProductCommentLike(): void
    {
        $repository = $this->getRepository();

        // 创建并保存测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);

        $repository->save($like);
        $likeId = $like->getId();

        // 验证点赞记录存在
        $this->assertNotNull($repository->find($likeId));

        // 测试删除
        $repository->remove($like);

        // 验证删除成功
        $this->assertEntityNotExists(ProductCommentLike::class, $likeId);
    }

    public function testFindByUserAndComment(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());
        $comment1 = $this->createTestComment($spu, $user1);
        $comment2 = $this->createTestComment($spu, $user2);

        // 用户1对评论1点赞
        $like1 = $this->createProductCommentLike($user1, $comment1, 1);
        $repository->save($like1);

        // 用户2对评论1点赞
        $like2 = $this->createProductCommentLike($user2, $comment1, 1);
        $repository->save($like2);

        // 用户1对评论2点赞
        $like3 = $this->createProductCommentLike($user1, $comment2, 1);
        $repository->save($like3);

        // 测试按用户和评论查询
        $user1Comment1Like = $repository->findOneBy([
            'user' => $user1,
            'productComment' => $comment1,
        ]);

        $this->assertNotNull($user1Comment1Like);
        $this->assertEquals($like1->getId(), $user1Comment1Like->getId());

        // 测试按评论查询所有点赞
        $comment1Likes = $repository->findBy(['productComment' => $comment1]);
        $this->assertCount(2, $comment1Likes);

        // 测试按用户查询所有点赞
        $user1Likes = $repository->findBy(['user' => $user1]);
        $this->assertCount(2, $user1Likes);
    }

    public function testToggleLikeStatus(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);

        $repository->save($like);

        // 验证初始状态
        $savedLike = $repository->find($like->getId());
        $this->assertNotNull($savedLike);
        $this->assertEquals(1, $savedLike->getStatus());

        // 切换状态为取消点赞
        $savedLike->setStatus(0);
        $repository->save($savedLike);

        // 验证状态已更新
        $updatedLike = $repository->find($like->getId());
        $this->assertNotNull($updatedLike);
        $this->assertEquals(0, $updatedLike->getStatus());

        // 再次切换状态为点赞
        $updatedLike->setStatus(1);
        $repository->save($updatedLike);

        // 验证状态再次更新
        $finalLike = $repository->find($like->getId());
        $this->assertNotNull($finalLike);
        $this->assertEquals(1, $finalLike->getStatus());
    }

    public function testCountLikesByComment(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $author);

        // 创建多个点赞用户
        for ($i = 1; $i <= 5; ++$i) {
            $user = $this->createNormalUser('test_user_' . uniqid());
            $like = $this->createProductCommentLike($user, $comment, $i <= 3 ? 1 : 0);
            $repository->save($like);
        }

        // 统计活跃点赞数
        $activeLikes = $repository->findBy([
            'productComment' => $comment,
            'status' => 1,
        ]);
        $this->assertCount(3, $activeLikes);

        // 统计所有点赞记录（包括取消的）
        $allLikes = $repository->findBy(['productComment' => $comment]);
        $this->assertCount(5, $allLikes);
    }

    private function createTestSpu(): Spu
    {
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setValid(true);

        $persistedSpu = $this->persistAndFlush($spu);
        $this->assertInstanceOf(Spu::class, $persistedSpu);

        return $persistedSpu;
    }

    private function createTestComment(Spu $spu, UserInterface $user): ProductComment
    {
        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContent('这是一条测试评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);

        $persistedComment = $this->persistAndFlush($comment);
        $this->assertInstanceOf(ProductComment::class, $persistedComment);

        return $persistedComment;
    }

    private function createProductCommentLike(UserInterface $user, ProductComment $comment, int $status): ProductCommentLike
    {
        $like = new ProductCommentLike();
        $like->setUser($user);
        $like->setProductComment($comment);
        $like->setStatus($status);

        return $like;
    }

    // 补充缺失的基础测试方法

    public function testClearMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like, true); // 立即持久化到数据库

        // 清除实体管理器缓存
        $repository->clear();

        // 验证缓存已清除，需要重新查询数据库
        $foundLike = $repository->find($like->getId());
        $this->assertNotNull($foundLike);
        $this->assertEquals(1, $foundLike->getStatus());

        $repository->remove($foundLike);
    }

    public function testFlushMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like, false); // 不立即刷新

        // 手动刷新到数据库
        $repository->flush();

        // 验证数据已刷新到数据库
        $foundLike = $repository->find($like->getId());
        $this->assertNotNull($foundLike);
        $this->assertEquals(1, $foundLike->getStatus());

        $repository->remove($foundLike);
    }

    public function testSaveAllMethod(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $author);

        $likes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $user = $this->createNormalUser('test_user_' . uniqid());
            $like = $this->createProductCommentLike($user, $comment, $i <= 2 ? 1 : 0);
            $likes[] = $like;
        }

        $repository->saveAll($likes);

        foreach ($likes as $like) {
            $this->assertNotNull($like->getId());
            $repository->remove($like, false);
        }
        $repository->flush();
    }

    public function testFindOneByProductCommentShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $comment1 = $this->createTestComment($spu, $author);
        $comment2 = $this->createTestComment($spu, $author);

        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建对不同评论的点赞
        $like1 = $this->createProductCommentLike($user, $comment1, 1);
        $repository->save($like1);

        $like2 = $this->createProductCommentLike($user, $comment2, 1);
        $repository->save($like2);

        // 测试按评论查询点赞
        $result = $repository->findOneBy(['productComment' => $comment1]);
        $this->assertNotNull($result);
        $this->assertEquals($like1->getId(), $result->getId());
        $foundComment = $result->getProductComment();
        $this->assertNotNull($foundComment);
        $this->assertEquals($comment1->getId(), $foundComment->getId());

        $repository->remove($like1, false);
        $repository->remove($like2, false);
        $repository->flush();
    }

    public function testFindByUserShouldReturnAllMatchingEntities(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建该用户对多个评论的点赞
        $likes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = $this->createTestComment($spu, $author);
            $like = $this->createProductCommentLike($user, $comment, 1);
            $repository->save($like, false);
            $likes[] = $like;
        }
        $repository->flush();

        // 按用户查询所有点赞
        $results = $repository->findBy(['user' => $user]);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertEquals($user, $result->getUser());
            $this->assertEquals(1, $result->getStatus());
        }

        foreach ($likes as $like) {
            $repository->remove($like, false);
        }
        $repository->flush();
    }

    public function testCountByAssociationProductCommentShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $comment1 = $this->createTestComment($spu, $author);
        $comment2 = $this->createTestComment($spu, $author);

        // 创建4个属于comment1的点赞
        $comment1Likes = [];
        for ($i = 1; $i <= 4; ++$i) {
            $user = $this->createNormalUser('test_user_' . uniqid());
            $like = $this->createProductCommentLike($user, $comment1, 1);
            $repository->save($like, false);
            $comment1Likes[] = $like;
        }

        // 创建2个属于comment2的点赞
        $comment2Likes = [];
        for ($i = 1; $i <= 2; ++$i) {
            $user = $this->createNormalUser('test_user_' . uniqid());
            $like = $this->createProductCommentLike($user, $comment2, 1);
            $repository->save($like, false);
            $comment2Likes[] = $like;
        }
        $repository->flush();

        $count = $repository->count(['productComment' => $comment1]);
        $this->assertSame(4, $count);

        foreach ($comment1Likes as $like) {
            $repository->remove($like, false);
        }
        foreach ($comment2Likes as $like) {
            $repository->remove($like, false);
        }
        $repository->flush();
    }

    public function testCountByAssociationUserShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $author = $this->createNormalUser('test_user_' . uniqid());
        $user1 = $this->createNormalUser('test_user_' . uniqid());
        $user2 = $this->createNormalUser('test_user_' . uniqid());

        // 创建user1的3个点赞
        $user1Likes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = $this->createTestComment($spu, $author);
            $like = $this->createProductCommentLike($user1, $comment, 1);
            $repository->save($like, false);
            $user1Likes[] = $like;
        }

        // 创建user2的2个点赞
        $user2Likes = [];
        for ($i = 1; $i <= 2; ++$i) {
            $comment = $this->createTestComment($spu, $author);
            $like = $this->createProductCommentLike($user2, $comment, 1);
            $repository->save($like, false);
            $user2Likes[] = $like;
        }
        $repository->flush();

        $count = $repository->count(['user' => $user1]);
        $this->assertSame(3, $count);

        foreach ($user1Likes as $like) {
            $repository->remove($like, false);
        }
        foreach ($user2Likes as $like) {
            $repository->remove($like, false);
        }
        $repository->flush();
    }

    // 补充缺失的可空字段测试（针对从 trait 继承的可空字段）

    // 补充关联查询测试方法（使用精确的方法名模式）
    public function testFindOneByProductCommentAssociationShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like);

        $result = $repository->findOneBy(['productComment' => $comment]);
        $this->assertNotNull($result);
        $this->assertEquals($like->getId(), $result->getId());
        $foundComment = $result->getProductComment();
        $this->assertNotNull($foundComment);
        $this->assertEquals($comment->getId(), $foundComment->getId());

        $repository->remove($like);
    }

    public function testFindByUserAssociationShouldReturnAllMatchingEntities(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $author = $this->createNormalUser('test_user_' . uniqid());

        // 创建该用户对多个评论的点赞
        $likes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $comment = $this->createTestComment($spu, $author);
            $like = $this->createProductCommentLike($user, $comment, 1);
            $repository->save($like, false);
            $likes[] = $like;
        }
        $repository->flush();

        $results = $repository->findBy(['user' => $user]);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertEquals($user, $result->getUser());
            $this->assertEquals(1, $result->getStatus());
        }

        foreach ($likes as $like) {
            $repository->remove($like, false);
        }
        $repository->flush();
    }

    // 根据 PHPStan 的具体期望，补充更多关联字段测试模式
    public function testFindOneByAssociationProductComment(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like);

        $result = $repository->findOneBy(['productComment' => $comment]);
        $this->assertNotNull($result);
        $this->assertEquals($comment, $result->getProductComment());

        $repository->remove($like);
    }

    public function testFindOneByAssociationUser(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like);

        $result = $repository->findOneBy(['user' => $user]);
        $this->assertNotNull($result);
        $this->assertEquals($user, $result->getUser());

        $repository->remove($like);
    }

    public function testFindOneByAssociationProductCommentShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like);

        $result = $repository->findOneBy(['productComment' => $comment]);
        $this->assertNotNull($result);
        $this->assertEquals($comment, $result->getProductComment());

        $repository->remove($like);
    }

    public function testFindOneByAssociationUserShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();
        $spu = $this->createTestSpu();
        $user = $this->createNormalUser('test_user_' . uniqid());
        $comment = $this->createTestComment($spu, $user);

        $like = $this->createProductCommentLike($user, $comment, 1);
        $repository->save($like);

        $result = $repository->findOneBy(['user' => $user]);
        $this->assertNotNull($result);
        $this->assertEquals($user, $result->getUser());

        $repository->remove($like);
    }
}
