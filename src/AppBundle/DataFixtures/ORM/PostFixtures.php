<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\DataFixtures\FixturesTrait;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Post;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the sample blog posts to load in the database before running the unit
 * and functional tests. Execute this command to load the data.
 *
 *   $ php bin/console doctrine:fixtures:load
 *
 * See https://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class PostFixtures extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use FixturesTrait;

    const LIMIT = 100;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        foreach (range(1, self::LIMIT) as $i) {
            $post = new Post();

            $post->setTitle($faker->sentence());
            $post->setSummary($faker->realText(200));
            $post->setSlug($this->container->get('slugger')->slugify($post->getTitle()));
            $post->setContent($faker->realText(800));
            // "References" are the way to share objects between fixtures defined
            // in different files. This reference has been added in the UserFixtures
            // file and it contains an instance of the User entity.
            $post->setAuthor($this->getReference('jane-admin'));
            $post->setPublishedAt($faker->dateTimeThisYear);

            // for aesthetic reasons, the first blog post always has 2 tags
            foreach ($this->getRandomTags($i > 0 ? mt_rand(0, 3) : 2) as $tag) {
                $post->addTag($tag);
            }

            foreach (range(1, rand(1, 150)) as $j) {
                $comment = new Comment();

                $comment->setAuthor($this->getReference('john-user'));
                $comment->setPublishedAt($faker->dateTimeThisYear);
                $comment->setContent($faker->realText());
                $comment->setPost($post);

                $manager->persist($comment);
                $post->addComment($comment);
            }

            $manager->persist($post);
        }

        $manager->flush();
    }

    /**
     * Instead of defining the exact order in which the fixtures files must be loaded,
     * this method defines which other fixtures this file depends on. Then, Doctrine
     * will figure out the best order to fit all the dependencies.
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            TagFixtures::class,
            UserFixtures::class,
        ];
    }

    private function getRandomTags($numTags = 0)
    {
        $tags = [];

        if (0 === $numTags) {
            return $tags;
        }

        $indexes = (array)array_rand($this->getTagNames(), $numTags);
        foreach ($indexes as $index) {
            $tags[] = $this->getReference('tag-' . $index);
        }

        return $tags;
    }
}
