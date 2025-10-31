<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCommentBundle\Repository\ProductCommentRepository;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

#[ORM\Table(name: 'product_comment', options: ['comment' => '产品评论'])]
#[ORM\Entity(repositoryClass: ProductCommentRepository::class)]
class ProductComment implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;
    use IpTraceableAware;

    #[ORM\ManyToOne(targetEntity: Contract::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contract $contract = null;

    #[ORM\ManyToOne(targetEntity: OrderProduct::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OrderProduct $orderProduct = null;

    #[ORM\ManyToOne(targetEntity: Sku::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Sku $sku = null;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Spu::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Spu $spu = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [0, 1, 2])]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '主题类型 0 首评； 1： 追评； 2回复'])]
    private int $topicType = 0;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['default' => 0, 'comment' => '上级ID'])]
    private int $parentId = 0;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['default' => 0, 'comment' => '根父级id'])]
    private int $rootParentId = 0;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Ip]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: false, options: ['default' => '', 'comment' => '客户端IP'])]
    private string $clientIp = '';

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserInterface $fromUser = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserInterface $toUser = null;

    #[Assert\NotNull]
    #[Assert\Choice(choices: [0, 1])]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '是否精选'])]
    private int $isGoods = 0;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [CommentStateEnum::class, 'cases'])]
    #[IndexColumn]
    #[ORM\Column(name: 'state', type: Types::STRING, nullable: false, enumType: CommentStateEnum::class, options: ['default' => 0, 'comment' => '状态'])]
    private CommentStateEnum $state;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 100)]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '评分'])]
    private int $rateNum = 0;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '点赞数量'])]
    private int $likeNum = 0;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, length: 65535, nullable: true, options: ['comment' => '评论内容'])]
    private ?string $content = null;

    /**
     * @var list<string>|null
     */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '图片JSON数据'])]
    private ?array $images = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => '', 'comment' => '视频'])]
    private string $video = '';

    #[Assert\NotNull]
    #[Assert\Choice(choices: [0, 1])]
    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '是否管理员回复'])]
    private int $isAdmin = 0;

    /**
     * @var Collection<int, ProductCommentLike>
     */
    #[ORM\OneToMany(mappedBy: 'productComment', targetEntity: ProductCommentLike::class, orphanRemoval: true)]
    private Collection $productCommentLikes;

    public function __construct()
    {
        $this->productCommentLikes = new ArrayCollection();
    }

    /**
     * @return Collection<int, ProductCommentLike>
     */
    public function getProductCommentLikes(): Collection
    {
        return $this->productCommentLikes;
    }

    public function addProductCommentLike(ProductCommentLike $productCommentLike): self
    {
        if (!$this->productCommentLikes->contains($productCommentLike)) {
            $this->productCommentLikes->add($productCommentLike);
            $productCommentLike->setProductComment($this);
        }

        return $this;
    }

    public function removeProductCommentLike(ProductCommentLike $productCommentLike): self
    {
        if ($this->productCommentLikes->removeElement($productCommentLike)) {
            // set the owning side to null (unless already changed)
            if ($productCommentLike->getProductComment() === $this) {
                $productCommentLike->setProductComment(null);
            }
        }

        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): void
    {
        $this->contract = $contract;
    }

    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    public function setSku(?Sku $sku): void
    {
        $this->sku = $sku;
    }

    public function getSpu(): ?Spu
    {
        return $this->spu;
    }

    public function setSpu(?Spu $spu): void
    {
        $this->spu = $spu;
    }

    public function getTopicType(): int
    {
        return $this->topicType;
    }

    public function setTopicType(int $topicType): void
    {
        $this->topicType = $topicType;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getRootParentId(): int
    {
        return $this->rootParentId;
    }

    public function setRootParentId(int $rootParentId): void
    {
        $this->rootParentId = $rootParentId;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function setClientIp(string $clientIp): void
    {
        $this->clientIp = $clientIp;
    }

    public function getFromUser(): ?UserInterface
    {
        return $this->fromUser;
    }

    public function setFromUser(?UserInterface $fromUser): void
    {
        $this->fromUser = $fromUser;
    }

    public function getToUser(): ?UserInterface
    {
        return $this->toUser;
    }

    public function setToUser(?UserInterface $toUser): void
    {
        $this->toUser = $toUser;
    }

    public function getIsGoods(): int
    {
        return $this->isGoods;
    }

    public function setIsGoods(int $isGoods): void
    {
        $this->isGoods = $isGoods;
    }

    public function getState(): CommentStateEnum
    {
        return $this->state;
    }

    public function setState(CommentStateEnum $state): void
    {
        $this->state = $state;
    }

    public function getRateNum(): int
    {
        return $this->rateNum;
    }

    public function setRateNum(int $rateNum): void
    {
        $this->rateNum = $rateNum;
    }

    public function getLikeNum(): int
    {
        return $this->likeNum;
    }

    public function setLikeNum(int $likeNum): void
    {
        $this->likeNum = $likeNum;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return list<string>|null
     */
    public function getImages(): ?array
    {
        return $this->images;
    }

    /**
     * @param list<string>|null $images
     */
    public function setImages(?array $images): void
    {
        $this->images = $images;
    }

    public function getVideo(): string
    {
        return $this->video;
    }

    public function setVideo(string $video): void
    {
        $this->video = $video;
    }

    public function getIsAdmin(): int
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(int $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function getOrderProduct(): ?OrderProduct
    {
        return $this->orderProduct;
    }

    public function setOrderProduct(?OrderProduct $orderProduct): void
    {
        $this->orderProduct = $orderProduct;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
