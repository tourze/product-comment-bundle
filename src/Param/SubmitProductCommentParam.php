<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

final readonly class SubmitProductCommentParam implements RpcParamInterface
{
    /**
     * @param list<string>|null $images
     */
    public function __construct(
        #[MethodParam(description: '订单商品id')]
        public string $orderProductId,
        #[MethodParam(description: '评论内容')]
        public string $content,
        #[MethodParam(description: '评论图片')]
        public ?array $images = null,
    ) {
    }
}
