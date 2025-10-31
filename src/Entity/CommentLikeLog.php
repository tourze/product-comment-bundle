<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\ProductCommentBundle\Repository\CommentLikeLogRepository;

#[ORM\Table(name: 'ims_member_like_comment_log', options: ['comment' => '评论被点赞记录'])]
#[ORM\Entity(repositoryClass: CommentLikeLogRepository::class)]
class CommentLikeLog implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[Assert\NotBlank]
    #[Assert\GreaterThan(value: 0)]
    #[ORM\Column(options: ['default' => 0, 'comment' => '评论ID'])]
    private int $commentId = 0;

    #[Assert\NotBlank]
    #[Assert\GreaterThan(value: 0)]
    #[IndexColumn]
    #[ORM\Column(options: ['default' => 0, 'comment' => '点赞人ID'])]
    private int $memberId = 0;

    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[ORM\Column(options: ['default' => 0, 'comment' => '状态'])]
    private ?bool $status = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommentId(): int
    {
        return $this->commentId;
    }

    public function setCommentId(int $commentId): void
    {
        $this->commentId = $commentId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
