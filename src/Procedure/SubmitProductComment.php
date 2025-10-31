<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
use OrderCoreBundle\Repository\OrderProductRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;

#[MethodTag(name: '产品模块')]
#[MethodDoc(summary: '提交评论')]
#[MethodExpose(method: 'SubmitProductComment')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
#[Autoconfigure(public: true)]
class SubmitProductComment extends LockableProcedure
{
    #[MethodParam(description: '订单商品id')]
    public string $orderProductId;

    #[MethodParam(description: '评论内容')]
    public string $content;

    /**
     * @var list<string>|null
     */
    #[MethodParam(description: '评论图片')]
    public ?array $images = null;

    public function __construct(
        private readonly OrderProductRepository $orderProductRepository,
        private readonly ProductCommentRepository $productCommentRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
        $content = trim($this->content);
        if ('' === $content) {
            throw new ApiException('请输入评论内容');
        }

        $orderProduct = $this->orderProductRepository->findOneBy([
            'id' => $this->orderProductId,
        ]);
        if (null === $orderProduct) {
            throw new ApiException('评论异常');
        }
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录');
        }
        $contract = $orderProduct->getContract();
        if (null === $contract) {
            throw new ApiException('订单合同不存在');
        }

        if ($contract->getUser()?->getUserIdentifier() !== $user->getUserIdentifier()) {
            throw new ApiException('操作不允许');
        }

        $productComment = $this->productCommentRepository->findOneBy([
            'orderProduct' => $orderProduct,
            'fromUser' => $contract->getUser(),
        ]);
        if (null !== $productComment) {
            throw new ApiException('您已提交过评论~');
        }

        $productComment = new ProductComment();
        $productComment->setContent($content);
        $productComment->setOrderProduct($orderProduct);
        $productComment->setContract($contract);
        $productComment->setSpu($orderProduct->getSpu());
        $productComment->setSku($orderProduct->getSku());
        $productComment->setFromUser($contract->getUser());
        $productComment->setRootParentId(0);
        $productComment->setParentId(0);
        $productComment->setState(CommentStateEnum::APPROVED);
        $productComment->setTopicType(1);
        $productComment->setImages($this->images);
        $productComment->setClientIp($this->requestStack->getMainRequest()?->getClientIp() ?? '');
        $this->entityManager->persist($productComment);
        $this->entityManager->flush();

        return [
            '__message' => '评论成功',
        ];
    }
}
