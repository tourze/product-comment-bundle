<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPCPaginatorBundle\Param\PaginatorParamInterface;

final readonly class GetProductCommentListParam implements PaginatorParamInterface
{
    public function __construct(
        #[MethodParam(description: '商品id')]
        public string $productId,
        #[MethodParam(description: 'skuId')]
        public ?string $skuId = null,
        #[MethodParam(description: '评论根节点id')]
        public string $rootParentId = '0',

        #[MethodParam(description: '每页条数')]
        #[Assert\Range(min: 1, max: 2000)]
        public int $pageSize = 10,

        #[MethodParam(description: '当前页数')]
        #[Assert\Range(min: 1, max: 1000)]
        public int $currentPage = 1,

        #[MethodParam(description: '上一次拉取时，最后一条数据的主键ID')]
        public ?int $lastId = null,
    ) {
    }
}
