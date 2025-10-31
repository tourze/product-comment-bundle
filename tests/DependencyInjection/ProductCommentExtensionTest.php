<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\ProductCommentBundle\DependencyInjection\ProductCommentExtension;

/**
 * @internal
 */
#[CoversClass(ProductCommentExtension::class)]
final class ProductCommentExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
