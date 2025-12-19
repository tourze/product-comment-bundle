<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Param;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

final readonly class ReplyProductCommentParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '评论id')]
        public string $contentId,
        #[MethodParam(description: '回复内容')]
        public string $content,
    ) {
    }
}
