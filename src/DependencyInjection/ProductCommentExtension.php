<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class ProductCommentExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return dirname(__DIR__) . '/Resources/config';
    }
}
