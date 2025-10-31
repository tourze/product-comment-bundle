<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductCommentBundle\Entity\CommentLikeLog;

/**
 * @internal
 */
#[CoversClass(CommentLikeLog::class)]
final class CommentLikeLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CommentLikeLog();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield from [
            'commentId' => ['commentId', 123],
            'memberId' => ['memberId', 456],
            'status' => ['status', true],
        ];
    }
}
