<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\ProductCommentBundle\Repository\ProductCommentLikeRepository;

#[ORM\Entity(repositoryClass: ProductCommentLikeRepository::class)]
#[ORM\Table(name: 'product_comment_like', options: ['comment' => '产品点赞表'])]
class ProductCommentLike implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;
    use IpTraceableAware;

    #[ORM\ManyToOne(targetEntity: ProductComment::class, inversedBy: 'productCommentLikes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductComment $productComment = null;

    #[Assert\NotNull]
    #[Assert\Choice(choices: [0, 1])]
    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '点赞状态 0 取消点赞， 1 已点赞'])]
    private ?int $status = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserInterface $user = null;

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getProductComment(): ?ProductComment
    {
        return $this->productComment;
    }

    public function setProductComment(?ProductComment $productComment): void
    {
        $this->productComment = $productComment;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
