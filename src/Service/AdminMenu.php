<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\ProductCommentBundle\Entity\CommentLikeLog;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;

/**
 * 产品评论管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('商品管理')) {
            $item->addChild('商品管理');
        }

        $productMenu = $item->getChild('商品管理');
        if (null === $productMenu) {
            return;
        }

        // 创建评论管理子菜单
        if (null === $productMenu->getChild('评论管理')) {
            $productMenu->addChild('评论管理')
                ->setAttribute('icon', 'fas fa-comments')
            ;
        }

        $commentMenu = $productMenu->getChild('评论管理');
        if (null === $commentMenu) {
            return;
        }

        // 产品评论菜单
        $commentMenu->addChild('产品评论')
            ->setUri($this->linkGenerator->getCurdListPage(ProductComment::class))
            ->setAttribute('icon', 'fas fa-comment')
        ;

        // 评论点赞菜单
        $commentMenu->addChild('评论点赞')
            ->setUri($this->linkGenerator->getCurdListPage(ProductCommentLike::class))
            ->setAttribute('icon', 'fas fa-thumbs-up')
        ;

        // 点赞记录日志菜单
        $commentMenu->addChild('点赞记录日志')
            ->setUri($this->linkGenerator->getCurdListPage(CommentLikeLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
