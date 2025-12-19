<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\ProductCommentBundle\Param\LikeProductCommentParam;

/**
 * @internal
 */
#[CoversClass(LikeProductCommentParam::class)]
final class LikeProductCommentParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new LikeProductCommentParam(
            contentId: 'comment-123',
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('comment-123', $param->contentId);
    }
}
