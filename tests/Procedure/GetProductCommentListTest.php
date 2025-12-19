<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Param\GetProductCommentListParam;
use Tourze\ProductCommentBundle\Procedure\GetProductCommentList;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * @internal
 */
#[CoversClass(GetProductCommentList::class)]
#[RunTestsInSeparateProcesses]
final class GetProductCommentListTest extends AbstractProcedureTestCase
{
    private GetProductCommentList $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetProductCommentList::class);
    }

    public function testExecuteWithValidProductIdAndUser(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        $spu->setValid(true); // 设置 valid 字段为 true
        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        // 设置参数
        $spuId = $spu->getId();
        $param = new GetProductCommentListParam(
            productId: (string) $spuId,
            currentPage: 1,
            pageSize: 10
        );

        // 执行
        $result = $this->procedure->execute($param);

        // 验证结果结构
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['pagination']);
        $this->assertArrayHasKey('total', $result['pagination']);
        $this->assertIsArray($result['list']);
    }

    public function testExecuteWithInvalidProductId(): void
    {
        // 模拟用户认证
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 使用无效的商品ID格式
        $param = new GetProductCommentListParam(
            productId: 'invalid_id_format',
            currentPage: 1,
            pageSize: 10
        );

        $this->expectException(ApiException::class);
        $this->procedure->execute($param);
    }

    public function testExecuteWithoutAuthentication(): void
    {
        // 不登录用户，直接测试
        $param = new GetProductCommentListParam(
            productId: '1',
            currentPage: 1,
            pageSize: 10
        );

        // 未认证时应该抛出异常
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录');
        $this->procedure->execute($param);
    }

    public function testExecuteWithRootParentIdFilter(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        $spu->setValid(true); // 设置 valid 字段为 true
        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        // 创建测试用的父评论
        $parentComment = new ProductComment();
        $parentComment->setSpu($spu);
        $parentComment->setContent('Parent comment');
        $parentComment->setState(CommentStateEnum::APPROVED);
        $parentComment->setFromUser($user);
        self::getEntityManager()->persist($parentComment);
        self::getEntityManager()->flush();

        // 测试根节点ID过滤
        $commentId = $parentComment->getId();
        $this->assertNotNull($commentId, 'Comment ID should not be null after persist and flush');
        $param = new GetProductCommentListParam(
            productId: (string) $spu->getId(),
            rootParentId: $commentId,
            currentPage: 1,
            pageSize: 10
        );

        // 执行
        $result = $this->procedure->execute($param);

        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);
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
