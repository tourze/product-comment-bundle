<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\ProductCommentBundle\ProductCommentBundle;

/**
 * @internal
 */
#[CoversClass(ProductCommentBundle::class)]
#[RunTestsInSeparateProcesses]
final class ProductCommentBundleTest extends AbstractBundleTestCase
{
}
