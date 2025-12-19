<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;
use Tourze\ProductCommentBundle\Param\LikeProductCommentParam;
use Tourze\ProductCommentBundle\Repository\ProductCommentLikeRepository;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;

#[MethodTag(name: '产品模块')]
#[MethodDoc(summary: '点赞/取消点赞评论')]
#[MethodExpose(method: 'LikeProductComment')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
#[Autoconfigure(public: true)]
final class LikeProductComment extends LockableProcedure
{
    public function __construct(
        private readonly ProductCommentLikeRepository $productCommentLikeRepository,
        private readonly ProductCommentRepository $productCommentRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @phpstan-param LikeProductCommentParam $param
     */
    public function execute(LikeProductCommentParam|RpcParamInterface $param): ArrayResult
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录');
        }

        $productComment = $this->productCommentRepository->findOneBy([
            'id' => $param->contentId,
        ]);
        if (null === $productComment) {
            throw new ApiException('不存在评论');
        }
        $productCommentLike = $this->productCommentLikeRepository->findOneBy([
            'user' => $user,
            'productComment' => $productComment,
        ]);

        if (null === $productCommentLike) {
            $productCommentLike = new ProductCommentLike();
            $productCommentLike->setUser($user);
            $productCommentLike->setProductComment($productComment);
            $productCommentLike->setStatus(1);
        } else {
            $productCommentLike->setStatus(abs(($productCommentLike->getStatus() ?? 0) - 1));
        }
        $this->entityManager->persist($productCommentLike);
        $this->entityManager->flush();

        return new ArrayResult([
            '__message' => 1 === $productCommentLike->getStatus() ? '点赞成功' : '取消点赞成功',
        ]);
    }
}
