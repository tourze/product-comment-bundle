<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Procedure;

use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\ProductCommentBundle\Procedure\SubmitProductComment;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * @internal
 */
#[CoversClass(SubmitProductComment::class)]
#[RunTestsInSeparateProcesses]
final class SubmitProductCommentTest extends AbstractProcedureTestCase
{
    private SubmitProductComment $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(SubmitProductComment::class);
    }

    public function testExecuteWithEmptyContent(): void
    {
        $this->procedure->orderProductId = '123';
        $this->procedure->content = '   ';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('请输入评论内容');
        $this->procedure->execute();
    }

    public function testExecuteWithNonExistentOrderProduct(): void
    {
        $this->procedure->orderProductId = '999999';
        $this->procedure->content = '评论内容';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('评论异常');
        $this->procedure->execute();
    }

    public function testExecuteWithoutAuthentication(): void
    {
        // 创建一个真实的订单产品，避免因为找不到订单产品而提前失败
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 创建SPU和SKU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $spu->setState(SpuState::ONLINE);
        self::getEntityManager()->persist($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setUnit('个');
        self::getEntityManager()->persist($sku);

        // 创建合同
        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-' . uniqid());
        $contract->setState(OrderState::PAID);
        $contract->setUser($user);
        self::getEntityManager()->persist($contract);

        // 创建订单产品
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSpu($spu);
        $orderProduct->setSku($sku);
        $orderProduct->setQuantity(1);
        self::getEntityManager()->persist($orderProduct);

        self::getEntityManager()->flush();

        $this->procedure->orderProductId = (string) $orderProduct->getId();
        $this->procedure->content = '评论内容';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录');
        $this->procedure->execute();
    }
}
