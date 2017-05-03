<?php

namespace AppBundle\Command;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Post;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Utils\Slugger;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePostsCommand extends ContainerAwareCommand
{

    /** @var ObjectManager */
    private $entityManager;

    /** @var  Slugger */
    private $slugger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:generate-posts')
            ->setDescription('Generates dummy posts to populate DB')
            ->addArgument('count', InputArgument::OPTIONAL, 'How many posts you want to generate?', 50);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->slugger = $this->getContainer()->get('slugger');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $input->getArgument('count');
        $faker = Factory::create();

        $author = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'jane_admin@symfony.com']);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john_user@symfony.com']);
        $tags = $this->entityManager->getRepository(Tag::class)->findAll();

        foreach (range(1, $count) as $i) {
            $post = new Post();

            $post->setTitle($faker->sentence());
            $post->setSummary($faker->realText(200));
            $post->setSlug($this->slugger->slugify($post->getTitle()));
            $post->setContent($faker->realText(800));
            $post->setAuthor($author);
            $post->setPublishedAt($faker->dateTimeThisYear);

            // for aesthetic reasons, the first blog post always has 2 tags
            foreach ($tags as $tag) {
                if (rand() % 4) {
                    continue;
                }

                $post->addTag($tag);
            }

            foreach (range(1, rand(1, 50)) as $j) {
                $comment = new Comment();

                $comment->setAuthor($user);
                $comment->setPublishedAt($faker->dateTimeThisYear);
                $comment->setContent($faker->realText());
                $comment->setPost($post);

                $this->entityManager->persist($comment);
                $post->addComment($comment);
            }

            $this->entityManager->persist($post);
            $output->writeln($i);
        }

        $this->entityManager->flush();
    }

}
