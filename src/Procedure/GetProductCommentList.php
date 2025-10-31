<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Procedure;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;
use Tourze\ProductCoreBundle\Service\SpuService;

#[MethodTag(name: '产品模块')]
#[MethodDoc(summary: '获取评论列表')]
#[MethodExpose(method: 'GetProductCommentList')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Autoconfigure(public: true)]
class GetProductCommentList extends BaseProcedure
{
    use PaginatorTrait;

    #[MethodParam(description: '商品id')]
    public string $productId;

    #[MethodParam(description: 'skuId')]
    public ?string $skuId = null;

    #[MethodParam(description: '评论根节点id')]
    public string $rootParentId = '0';

    public function __construct(
        private readonly Security $security,
        private readonly ProductCommentRepository $productCommentRepository,
        private readonly SpuService $spuService,
    ) {
    }

    public function execute(): array
    {
        $user = $this->security->getUser();

        // 检查用户是否已认证
        if (null === $user) {
            throw new ApiException('用户未登录');
        }

        $spu = $this->spuService->findValidSpuById($this->productId);

        if (null === $spu) {
            throw new ApiException('商品不存在或已下架');
        }
        $select = [
            'c.id',
            'c.parentId',
            'c.content',
            'c.createTime',
            'u.id as userId',
            'u.nickName',
            'u.avatar',
            '(SELECT count(1) AS total2 FROM ' . ProductCommentLike::class . ' l2 WHERE c.id = l2.productComment ) as likeCount',
        ];
        $select[] = '(SELECT count(1) AS total1 FROM ' . ProductCommentLike::class . ' l WHERE c.id = l.productComment and l.user =:user2 ) as isLike';
        if ('0' === $this->rootParentId) {
            $select[] = '(SELECT count(1) AS childCommentTotal2 FROM ' . ProductComment::class . ' tc WHERE tc.rootParentId=c.id ) as childCommentTotal';
        }

        if ((int) $this->rootParentId > 0) {
            $select[] = 'toUser.id as toUserId';
            $select[] = 'toUser.nickName as toUserNickName';
            $select[] = 'toUser.avatar as toUserAvatar';
        }

        $qb = $this->productCommentRepository
            ->createQueryBuilder('c')
            ->select($select)
            ->where('c.spu =:spu')
            ->andWhere('c.rootParentId =:rootParentId')
            ->andWhere('c.state =:commentStatus')
            ->setParameter('spu', $spu)
            ->setParameter('commentStatus', CommentStateEnum::APPROVED)
            ->setParameter('rootParentId', $this->rootParentId)
            ->leftJoin('c.fromUser', 'u')
            ->addOrderBy('c.id', 'DESC')
        ;

        $qb->setParameter('user2', $user);
        if ((int) $this->rootParentId > 0) {
            $qb->leftJoin('c.toUser', 'toUser');
        }

        try {
            return $this->fetchList($qb, $this->format(...));
        } catch (\Throwable $exception) {
            throw new ApiException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function format(array $item): array
    {
        $item['like'] = $item['isLike'] > 0;
        unset($item['isLike']);

        return $item;
    }
}
