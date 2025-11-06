<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Procedure\LikeProductComment;
use Tourze\ProductCommentBundle\Repository\ProductCommentLikeRepository;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * @internal
 */
#[CoversClass(LikeProductComment::class)]
#[RunTestsInSeparateProcesses]
final class LikeProductCommentTest extends AbstractProcedureTestCase
{
    private LikeProductComment $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(LikeProductComment::class);
    }

    public function testExecuteSuccessfullyLikesComment(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        // 创建测试用的评论
        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setContent('Test comment');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setFromUser($user);
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();

        // 设置参数
        $commentId = $comment->getId();
        $this->assertNotNull($commentId, 'Comment ID should not be null after persist and flush');
        $this->procedure->contentId = $commentId;

        // 执行点赞
        $result = $this->procedure->execute();

        // 验证结果
        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('点赞成功', $result['__message']);

        // 验证数据库中的点赞记录
        $likeRepository = self::getService(ProductCommentLikeRepository::class);
        $like = $likeRepository->findOneBy([
            'user' => $user,
            'productComment' => $comment,
        ]);
        $this->assertNotNull($like);
        $this->assertEquals(1, $like->getStatus());
    }

    public function testExecuteSuccessfullyUnlikesComment(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        // 创建测试用的评论
        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setContent('Test comment');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setFromUser($user);
        self::getEntityManager()->persist($comment);

        // 创建已存在的点赞记录
        $existingLike = new ProductCommentLike();
        $existingLike->setUser($user);
        $existingLike->setProductComment($comment);
        $existingLike->setStatus(1);
        self::getEntityManager()->persist($existingLike);
        self::getEntityManager()->flush();

        // 设置参数
        $commentId = $comment->getId();
        $this->assertNotNull($commentId, 'Comment ID should not be null after persist and flush');
        $this->procedure->contentId = $commentId;

        // 执行取消点赞
        $result = $this->procedure->execute();

        // 验证结果
        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('取消点赞成功', $result['__message']);

        // 验证数据库中的点赞记录状态
        $likeRepository = self::getService(ProductCommentLikeRepository::class);
        $like = $likeRepository->findOneBy([
            'user' => $user,
            'productComment' => $comment,
        ]);
        $this->assertNotNull($like);
        $this->assertEquals(0, $like->getStatus());
    }

    public function testExecuteWithNonExistentComment(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        $this->procedure->contentId = '999999';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('不存在评论');
        $this->procedure->execute();
    }

    public function testExecuteWithoutAuthentication(): void
    {
        $this->procedure->contentId = '12345';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录');
        $this->procedure->execute();
    }

    public function testExecuteToggleLikeStatus(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        // 创建测试用的评论
        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setContent('Test comment');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setFromUser($user);
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();

        // 设置参数
        $commentId = $comment->getId();
        $this->assertNotNull($commentId, 'Comment ID should not be null after persist and flush');
        $this->procedure->contentId = $commentId;

        // 第一次执行 - 点赞
        $result1 = $this->procedure->execute();
        $this->assertEquals('点赞成功', $result1['__message']);

        // 第二次执行 - 取消点赞
        $result2 = $this->procedure->execute();
        $this->assertEquals('取消点赞成功', $result2['__message']);

        // 第三次执行 - 再次点赞
        $result3 = $this->procedure->execute();
        $this->assertEquals('点赞成功', $result3['__message']);
    }

    /**
     * 直接设置认证用户（避免服务定位器问题）
     */
    private function setAuthenticatedUserDirect(UserInterface $user): void
    {
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);
    }
}
