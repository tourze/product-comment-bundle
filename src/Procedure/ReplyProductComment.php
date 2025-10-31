<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
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
#[MethodDoc(summary: '回复评论')]
#[MethodExpose(method: 'ReplyProductComment')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
#[Autoconfigure(public: true)]
class ReplyProductComment extends LockableProcedure
{
    #[MethodParam(description: '评论id')]
    public string $contentId;

    #[MethodParam(description: '回复内容')]
    public string $content;

    public function __construct(
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

        $productComment = $this->productCommentRepository->findOneBy([
            'id' => $this->contentId,
        ]);
        if (null === $productComment) {
            throw new ApiException('不存在评论');
        }

        $replyComment = new ProductComment();
        $replyComment->setContent($content);
        $replyComment->setOrderProduct($productComment->getOrderProduct());
        $replyComment->setContract($productComment->getContract());
        $replyComment->setSpu($productComment->getSpu());
        $replyComment->setSku($productComment->getSku());
        $replyComment->setTopicType(2);
        $replyComment->setState(CommentStateEnum::APPROVED);
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录');
        }
        $replyComment->setFromUser($user);
        $replyComment->setToUser($productComment->getFromUser());
        $replyComment->setRootParentId(0 === $productComment->getRootParentId() ? (int) $productComment->getId() : $productComment->getRootParentId());
        $replyComment->setParentId((int) $productComment->getId());
        $productComment->setClientIp($this->requestStack->getMainRequest()?->getClientIp() ?? '');
        $this->entityManager->persist($replyComment);
        $this->entityManager->flush();

        return [
            '__message' => '回复成功',
        ];
    }
}
