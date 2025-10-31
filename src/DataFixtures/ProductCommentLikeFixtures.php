<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineResolveTargetEntityBundle\Service\ResolveTargetEntityService;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;

#[When(env: 'test')]
#[When(env: 'dev')]
class ProductCommentLikeFixtures extends Fixture implements DependentFixtureInterface
{
    public const PRODUCT_COMMENT_LIKE_REFERENCE_PREFIX = 'product_comment_like_';
    public const PRODUCT_COMMENT_LIKE_COUNT = 200;

    public function __construct(
        private readonly ResolveTargetEntityService $resolveTargetEntityService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 获取用户实体类
        $userClass = $this->resolveTargetEntityService->findEntityClass(UserInterface::class);

        for ($i = 0; $i < self::PRODUCT_COMMENT_LIKE_COUNT; ++$i) {
            $productCommentLike = $this->createProductCommentLike($manager, $userClass);
            $manager->persist($productCommentLike);
            $this->addReference(self::PRODUCT_COMMENT_LIKE_REFERENCE_PREFIX . $i, $productCommentLike);
        }

        $manager->flush();
    }

    private function createProductCommentLike(ObjectManager $manager, string $userClass): ProductCommentLike
    {
        $productCommentLike = new ProductCommentLike();

        $commentIndex = random_int(0, ProductCommentFixtures::PRODUCT_COMMENT_COUNT - 1);
        $productComment = $this->getReference(
            ProductCommentFixtures::PRODUCT_COMMENT_REFERENCE_PREFIX . $commentIndex,
            ProductComment::class
        );

        // 创建测试用户
        $testUser = new $userClass();
        if (method_exists($testUser, 'setUsername')) {
            $testUser->setUsername('test_user_' . uniqid());
        }
        if (method_exists($testUser, 'setNickName')) {
            $testUser->setNickName('测试用户 ' . uniqid());
        }
        if (method_exists($testUser, 'setValid')) {
            $testUser->setValid(true);
        }
        $manager->persist($testUser);

        $productCommentLike->setProductComment($productComment);
        if ($testUser instanceof UserInterface) {
            $productCommentLike->setUser($testUser);
        }
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
