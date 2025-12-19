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
use Tourze\ProductCommentBundle\Param\GetProductCommentListParam;
use Tourze\ProductCommentBundle\Procedure\GetProductCommentList;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * @internal
 */
#[CoversClass(GetProductCommentList::class)]
#[RunTestsInSeparateProcesses]
final class GetProductCommentListSimpleTest extends AbstractProcedureTestCase
{
    private GetProductCommentList $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetProductCommentList::class);
    }

    public function testExecuteWithoutCommentsReturnsEmptyList(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        $spu->setValid(true);
        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        // 设置参数
        $param = new GetProductCommentListParam(
            productId: (string) $spu->getId(),
            currentPage: 1,
            pageSize: 10
        );

        $result = $this->procedure->execute($param);

        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['list']);
        $this->assertEmpty($result['list']);
    }

    public function testProductIdValidation(): void
    {
        // 模拟用户认证
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 设置参数 - 使用无效的商品ID格式
        $param = new GetProductCommentListParam(
            productId: 'invalid_id',
            currentPage: 1,
            pageSize: 10
        );

        $this->expectException(ApiException::class);
        $this->procedure->execute($param);
    }

    public function testPaginationParameters(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUserDirect($user);

        // 创建测试用的SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        $spu->setValid(true);
        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        // 设置参数
        $param = new GetProductCommentListParam(
            productId: (string) $spu->getId(),
            currentPage: 2,
            pageSize: 5
        );

        $result = $this->procedure->execute($param);

        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['pagination']);
        $this->assertArrayHasKey('current', $result['pagination']);
        $this->assertArrayHasKey('pageSize', $result['pagination']);
        $this->assertArrayHasKey('total', $result['pagination']);
        $this->assertEquals(2, $result['pagination']['current']);
        $this->assertEquals(5, $result['pagination']['pageSize']);
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
