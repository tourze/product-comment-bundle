<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Procedure;

use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Param\ReplyProductCommentParam;
use Tourze\ProductCommentBundle\Procedure\ReplyProductComment;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * @internal
 */
#[CoversClass(ReplyProductComment::class)]
#[RunTestsInSeparateProcesses]
final class ReplyProductCommentTest extends AbstractProcedureTestCase
{
    private ReplyProductComment $procedure;

    private ProductCommentRepository $commentRepository;

    protected function onSetUp(): void
    {
        $this->commentRepository = self::getService(ProductCommentRepository::class);
        $this->procedure = self::getService(ReplyProductComment::class);
    }

    public function testExecuteSuccessfullyRepliesComment(): void
    {
        // 创建测试用户
        $originalUser = $this->createNormalUser('original@example.com', 'password123');
        $replyUser = $this->createNormalUser('reply@example.com', 'password123');

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        // 创建测试用的Contract
        $contract = new Contract();
        $contract->setUser($originalUser);
        $contract->setState(OrderState::INIT);
        self::getEntityManager()->persist($contract);

        // 创建测试用的OrderProduct
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSpu($spu);
        self::getEntityManager()->persist($orderProduct);

        // 创建原始评论
        $originalComment = new ProductComment();
        $originalComment->setSpu($spu);
        $originalComment->setFromUser($originalUser);
        $originalComment->setContract($contract);
        $originalComment->setOrderProduct($orderProduct);
        $originalComment->setContent('这是一条原始评论');
        $originalComment->setState(CommentStateEnum::APPROVED);
        $originalComment->setRootParentId(0);
        $originalComment->setParentId(0);
        $originalComment->setTopicType(0);
        self::getEntityManager()->persist($originalComment);
        self::getEntityManager()->flush();

        // 模拟回复用户认证
        $this->setAuthenticatedUserDirect($replyUser);

        // 设置参数
        $param = new ReplyProductCommentParam(
            contentId: (string) $originalComment->getId(),
            content: '这是一条回复评论'
        );

        // 执行回复
        $result = $this->procedure->execute($param);

        // 验证结果
        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('回复成功', $result['__message']);

        // 验证数据库中的回复记录
        $replyComment = $this->commentRepository->findOneBy([
            'parentId' => $originalComment->getId(),
            'fromUser' => $replyUser,
        ]);

        $this->assertNotNull($replyComment);
        $this->assertEquals('这是一条回复评论', $replyComment->getContent());
        $this->assertEquals($originalComment->getId(), $replyComment->getParentId());
        $this->assertEquals($originalComment->getId(), $replyComment->getRootParentId());
        $this->assertEquals($originalUser, $replyComment->getToUser());
        $this->assertEquals($replyUser, $replyComment->getFromUser());
        $this->assertEquals(2, $replyComment->getTopicType()); // 2 表示回复
        $this->assertEquals(CommentStateEnum::APPROVED, $replyComment->getState());
    }

    public function testExecuteReplyToNestedComment(): void
    {
        // 创建测试用户
        $originalUser = $this->createNormalUser('original@example.com', 'password123');
        $firstReplyUser = $this->createNormalUser('firstreply@example.com', 'password123');
        $secondReplyUser = $this->createNormalUser('secondreply@example.com', 'password123');

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        // 创建测试用的Contract
        $contract = new Contract();
        $contract->setUser($originalUser);
        $contract->setState(OrderState::INIT);
        self::getEntityManager()->persist($contract);

        // 创建测试用的OrderProduct
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSpu($spu);
        self::getEntityManager()->persist($orderProduct);

        // 创建原始评论
        $originalComment = new ProductComment();
        $originalComment->setSpu($spu);
        $originalComment->setFromUser($originalUser);
        $originalComment->setContract($contract);
        $originalComment->setOrderProduct($orderProduct);
        $originalComment->setContent('这是一条原始评论');
        $originalComment->setState(CommentStateEnum::APPROVED);
        $originalComment->setRootParentId(0);
        $originalComment->setParentId(0);
        $originalComment->setTopicType(0);
        self::getEntityManager()->persist($originalComment);

        // 创建第一层回复
        $firstReply = new ProductComment();
        $firstReply->setSpu($spu);
        $firstReply->setFromUser($firstReplyUser);
        $firstReply->setToUser($originalComment->getFromUser());
        $firstReply->setContract($contract);
        $firstReply->setOrderProduct($orderProduct);
        $firstReply->setContent('这是一条回复评论');
        $firstReply->setState(CommentStateEnum::APPROVED);
        $firstReply->setRootParentId((int) $originalComment->getId());
        $firstReply->setParentId((int) $originalComment->getId());
        $firstReply->setTopicType(2);
        self::getEntityManager()->persist($firstReply);
        self::getEntityManager()->flush();

        // 模拟第二回复用户认证
        $this->setAuthenticatedUserDirect($secondReplyUser);

        // 回复第一层回复
        $param = new ReplyProductCommentParam(
            contentId: (string) $firstReply->getId(),
            content: '这是对回复的回复'
        );

        // 执行回复
        $result = $this->procedure->execute($param);

        // 验证结果
        $this->assertEquals('回复成功', $result['__message']);

        // 验证数据库中的嵌套回复记录
        $nestedReply = $this->commentRepository->findOneBy([
            'parentId' => $firstReply->getId(),
            'fromUser' => $secondReplyUser,
        ]);

        $this->assertNotNull($nestedReply);
        $this->assertEquals('这是对回复的回复', $nestedReply->getContent());
        $this->assertEquals($firstReply->getId(), $nestedReply->getParentId());
        $this->assertEquals($originalComment->getId(), $nestedReply->getRootParentId()); // 根父级仍然是原始评论
        $this->assertEquals($firstReplyUser, $nestedReply->getToUser());
    }

    public function testExecuteWithEmptyContent(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $param = new ReplyProductCommentParam(
            contentId: '123',
            content: '   ' // 空白内容
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('请输入评论内容');
        $this->procedure->execute($param);
    }

    public function testExecuteWithNonExistentComment(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $param = new ReplyProductCommentParam(
            contentId: '999999',
            content: '回复内容'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('不存在评论');
        $this->procedure->execute($param);
    }

    public function testExecuteWithoutAuthentication(): void
    {
        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        $user = $this->createNormalUser('test@example.com', 'password123');

        // 创建测试用的Contract
        $contract = new Contract();
        $contract->setUser($user);
        $contract->setState(OrderState::INIT);
        self::getEntityManager()->persist($contract);

        // 创建测试用的OrderProduct
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSpu($spu);
        self::getEntityManager()->persist($orderProduct);

        // 创建评论
        $comment = new ProductComment();
        $comment->setSpu($spu);
        $comment->setFromUser($user);
        $comment->setContract($contract);
        $comment->setOrderProduct($orderProduct);
        $comment->setContent('测试评论');
        $comment->setState(CommentStateEnum::APPROVED);
        $comment->setRootParentId(0);
        $comment->setParentId(0);
        $comment->setTopicType(0);
        self::getEntityManager()->persist($comment);
        self::getEntityManager()->flush();

        $param = new ReplyProductCommentParam(
            contentId: (string) $comment->getId(),
            content: '回复内容'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录');
        $this->procedure->execute($param);
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
