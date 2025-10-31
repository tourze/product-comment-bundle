<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 订单商品评论状态枚举
 */
enum CommentStateEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case POSTPONED = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::APPROVED => '已公开',
            self::REJECTED => '不通过',
            self::POSTPONED => '不显示',
        };
    }
}
