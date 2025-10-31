<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductCommentBundle\Entity\ProductComment;

/**
 * @internal
 */
#[CoversClass(ProductComment::class)]
final class ProductCommentTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ProductComment();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield from [
            'topicType' => ['topicType', 1],
            'parentId' => ['parentId', 123],
            'rootParentId' => ['rootParentId', 0],
            'clientIp' => ['clientIp', '192.168.1.1'],
            'isGoods' => ['isGoods', 1],
            'rateNum' => ['rateNum', 5],
            'likeNum' => ['likeNum', 10],
            'content' => ['content', 'Test comment'],
            'images' => ['images', ['image1.jpg', 'image2.jpg']],
            'video' => ['video', 'video.mp4'],
            'isAdmin' => ['isAdmin', 0],
        ];
    }
}
