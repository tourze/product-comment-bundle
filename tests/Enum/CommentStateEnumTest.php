<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;

/**
 * @internal
 */
#[CoversClass(CommentStateEnum::class)]
final class CommentStateEnumTest extends AbstractEnumTestCase
{
    public function testGenOptions(): void
    {
        $options = CommentStateEnum::genOptions();
        $this->assertIsArray($options);
        $this->assertCount(count(CommentStateEnum::cases()), $options);

        foreach ($options as $item) {
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('label', $item);
        }
    }

    public function testToArray(): void
    {
        foreach (CommentStateEnum::cases() as $case) {
            $result = $case->toArray();
            $this->assertIsArray($result);
            $this->assertArrayHasKey('value', $result);
            $this->assertArrayHasKey('label', $result);
            $this->assertSame($case->value, $result['value']);
            $this->assertSame($case->getLabel(), $result['label']);
        }
    }
}
