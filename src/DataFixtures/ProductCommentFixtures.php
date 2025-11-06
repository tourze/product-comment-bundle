<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;

#[When(env: 'test')]
#[When(env: 'dev')]
class ProductCommentFixtures extends Fixture
{
    public const PRODUCT_COMMENT_REFERENCE_PREFIX = 'product_comment_';
    public const PRODUCT_COMMENT_COUNT = 100;

    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::PRODUCT_COMMENT_COUNT; ++$i) {
            $productComment = $this->createProductComment();
            $manager->persist($productComment);
            $this->addReference(self::PRODUCT_COMMENT_REFERENCE_PREFIX . $i, $productComment);
        }

        $manager->flush();
    }

    private function createProductComment(): ProductComment
    {
        $productComment = new ProductComment();

        $productComment->setTopicType($this->faker->numberBetween(0, 2));
        $productComment->setParentId($this->faker->boolean(30) ? $this->faker->numberBetween(1, 50) : 0);
        $productComment->setRootParentId($this->faker->boolean(20) ? $this->faker->numberBetween(1, 20) : 0);
        $productComment->setClientIp($this->faker->ipv4());
        $productComment->setIsGoods($this->faker->numberBetween(0, 1));
        $productComment->setState(CommentStateEnum::APPROVED);
        $productComment->setRateNum($this->faker->numberBetween(0, 100));
        $productComment->setLikeNum($this->faker->numberBetween(0, 500));
        $productComment->setContent($this->faker->paragraph());

        if ($this->faker->boolean(30)) {
            $productComment->setImages([
                $this->faker->imageUrl(400, 300),
                $this->faker->imageUrl(400, 300),
            ]);
        }

        if ($this->faker->boolean(10)) {
            $productComment->setVideo($this->faker->url());
        }

        $productComment->setIsAdmin($this->faker->numberBetween(0, 1));

        $createTime = $this->faker->dateTimeBetween('-60 days', 'now');
        $productComment->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        return $productComment;
    }
}
