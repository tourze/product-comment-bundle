<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;

/**
 * @internal
 */
#[CoversClass(ProductCommentLike::class)]
final class ProductCommentLikeTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ProductCommentLike();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield from [
            'status' => ['status', 1],
        ];
    }
}
