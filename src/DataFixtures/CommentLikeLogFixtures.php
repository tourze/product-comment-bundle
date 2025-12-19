<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\ProductCommentBundle\Entity\CommentLikeLog;

#[When(env: 'test')]
#[When(env: 'dev')]
final class CommentLikeLogFixtures extends Fixture
{
    public const COMMENT_LIKE_LOG_REFERENCE_PREFIX = 'comment_like_log_';
    public const COMMENT_LIKE_LOG_COUNT = 50;

    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::COMMENT_LIKE_LOG_COUNT; ++$i) {
            $commentLikeLog = $this->createCommentLikeLog();
            $manager->persist($commentLikeLog);
            $this->addReference(self::COMMENT_LIKE_LOG_REFERENCE_PREFIX . $i, $commentLikeLog);
        }

        $manager->flush();
    }

    private function createCommentLikeLog(): CommentLikeLog
    {
        $commentLikeLog = new CommentLikeLog();
        $commentLikeLog->setCommentId($this->faker->numberBetween(1, 100));
        $commentLikeLog->setMemberId($this->faker->numberBetween(1, 1000));
        $commentLikeLog->setStatus($this->faker->boolean());

        $createTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $commentLikeLog->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        return $commentLikeLog;
    }
}
