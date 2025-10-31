<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Tests\Service;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\ProductCommentBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testInvokeCreatesExpectedMenuStructure(): void
    {
        $factory = new MenuFactory();
        $rootItem = $factory->createItem('root');

        // 执行菜单生成
        ($this->adminMenu)($rootItem);

        // 验证商品管理菜单被创建
        $productMenu = $rootItem->getChild('商品管理');
        $this->assertNotNull($productMenu, 'Should create "商品管理" menu');

        // 验证评论管理子菜单被创建
        $commentMenu = $productMenu->getChild('评论管理');
        $this->assertNotNull($commentMenu, 'Should create "评论管理" sub-menu');
        $this->assertEquals('fas fa-comments', $commentMenu->getAttribute('icon'));

        // 验证产品评论菜单项
        $productCommentItem = $commentMenu->getChild('产品评论');
        $this->assertNotNull($productCommentItem, 'Should create "产品评论" menu item');
        $this->assertEquals('fas fa-comment', $productCommentItem->getAttribute('icon'));
        $this->assertNotEmpty($productCommentItem->getUri(), 'Menu item should have URI');

        // 验证评论点赞菜单项
        $likeItem = $commentMenu->getChild('评论点赞');
        $this->assertNotNull($likeItem, 'Should create "评论点赞" menu item');
        $this->assertEquals('fas fa-thumbs-up', $likeItem->getAttribute('icon'));
        $this->assertNotEmpty($likeItem->getUri(), 'Menu item should have URI');

        // 验证点赞记录日志菜单项
        $likeLogItem = $commentMenu->getChild('点赞记录日志');
        $this->assertNotNull($likeLogItem, 'Should create "点赞记录日志" menu item');
        $this->assertEquals('fas fa-history', $likeLogItem->getAttribute('icon'));
        $this->assertNotEmpty($likeLogItem->getUri(), 'Menu item should have URI');
    }
}
