<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use OrderCoreBundle\OrderCoreBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;
use Tourze\JsonRPCLockBundle\JsonRPCLockBundle;
use Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle;
use Tourze\JsonRPCSecurityBundle\JsonRPCSecurityBundle;
use Tourze\ProductCoreBundle\ProductCoreBundle;

final class ProductCommentBundle extends Bundle implements BundleDependencyInterface
{
    /**
     * @return array<class-string<Bundle>, array<string, bool>>
     */
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            JsonRPCPaginatorBundle::class => ['all' => true],
            JsonRPCLockBundle::class => ['all' => true],
            ProductCoreBundle::class => ['all' => true],
            OrderCoreBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            JsonRPCSecurityBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
    }
}
