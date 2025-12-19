<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\ProductCommentBundle\Param\ReplyProductCommentParam;

/**
 * @internal
 */
#[CoversClass(ReplyProductCommentParam::class)]
final class ReplyProductCommentParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new ReplyProductCommentParam(
            contentId: 'comment-123',
            content: 'Test reply content',
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('comment-123', $param->contentId);
        $this->assertSame('Test reply content', $param->content);
    }
}
