<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;
use Tourze\UserServiceContracts\UserManagerInterface;

#[When(env: 'test')]
#[When(env: 'dev')]
final class ProductCommentLikeFixtures extends Fixture implements DependentFixtureInterface
{
    public const PRODUCT_COMMENT_LIKE_REFERENCE_PREFIX = 'product_comment_like_';
    public const PRODUCT_COMMENT_LIKE_COUNT = 200;

    public function __construct(
        private readonly UserManagerInterface $userManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::PRODUCT_COMMENT_LIKE_COUNT; ++$i) {
            $productCommentLike = $this->createProductCommentLike();
            $manager->persist($productCommentLike);
            $this->addReference(self::PRODUCT_COMMENT_LIKE_REFERENCE_PREFIX . $i, $productCommentLike);
        }

        $manager->flush();
    }

    private function createProductCommentLike(): ProductCommentLike
    {
        $productCommentLike = new ProductCommentLike();

        $commentIndex = random_int(0, ProductCommentFixtures::PRODUCT_COMMENT_COUNT - 1);
        $productComment = $this->getReference(
            ProductCommentFixtures::PRODUCT_COMMENT_REFERENCE_PREFIX . $commentIndex,
            ProductComment::class
        );

        // 通过 UserManager 创建测试用户
        $uniqueId = uniqid();
        $testUser = $this->userManager->createUser(
            userIdentifier: 'test_user_' . $uniqueId,
            nickName: '测试用户 ' . $uniqueId,
        );
        $this->userManager->saveUser($testUser);

        $productCommentLike->setProductComment($productComment);
        $productCommentLike->setUser($testUser);
        $productCommentLike->setStatus(random_int(0, 1));

        $createTime = (new \DateTime())->modify('-' . random_int(1, 30) . ' days');
        $productCommentLike->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        return $productCommentLike;
    }

    public function getDependencies(): array
    {
        return [
            ProductCommentFixtures::class,
        ];
    }
}
