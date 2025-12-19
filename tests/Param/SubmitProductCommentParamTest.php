<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\ProductCommentBundle\Param\SubmitProductCommentParam;

/**
 * @internal
 */
#[CoversClass(SubmitProductCommentParam::class)]
final class SubmitProductCommentParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new SubmitProductCommentParam(
            orderProductId: 'order-product-123',
            content: 'Test comment content',
            images: ['image1.jpg', 'image2.jpg'],
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('order-product-123', $param->orderProductId);
        $this->assertSame('Test comment content', $param->content);
        $this->assertSame(['image1.jpg', 'image2.jpg'], $param->images);
    }

    public function testParamWithNullImages(): void
    {
        $param = new SubmitProductCommentParam(
            orderProductId: 'order-product-456',
            content: 'Test comment without images',
        );

        $this->assertSame('order-product-456', $param->orderProductId);
        $this->assertSame('Test comment without images', $param->content);
        $this->assertNull($param->images);
    }
}
