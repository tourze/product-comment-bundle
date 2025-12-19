<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\ProductCommentBundle\Param\GetProductCommentListParam;

/**
 * @internal
 */
#[CoversClass(GetProductCommentListParam::class)]
final class GetProductCommentListParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new GetProductCommentListParam(
            productId: 'product-123',
            skuId: 'sku-456',
            rootParentId: '10',
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('product-123', $param->productId);
        $this->assertSame('sku-456', $param->skuId);
        $this->assertSame('10', $param->rootParentId);
    }

    public function testParamWithDefaultValues(): void
    {
        $param = new GetProductCommentListParam(
            productId: 'product-123',
        );

        $this->assertSame('product-123', $param->productId);
        $this->assertNull($param->skuId);
        $this->assertSame('0', $param->rootParentId);
    }
}
