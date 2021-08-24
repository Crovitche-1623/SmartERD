<?php

declare(strict_types = 1);

namespace App\Command;

use App\DataFixtures\{ProjectFixtures, UserFixtures};
use App\Entity\{Entity, Project, User};
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Faker\{Factory, Generator};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class EntityCreateCommand extends Command
{
    private Generator $faker;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->faker = Factory::create('fr_CH');

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('erd:entity:create')
            ->setDescription('Create an ERD Entity by asking few questions')
            ->addArgument('ownerUsername', InputArgument::OPTIONAL, 'The owner name')
            ->addArgument('projectName', InputArgument::OPTIONAL, 'The project name')
            ->addArgument('name', InputArgument::OPTIONAL, 'The project title')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('SmartERD - Entity creator assistant');
        $io->text('Answer all the questions or press <return> to choose the [<comment>default value</comment>]');

        // TODO: Add validator to questions when Reusable sets of constraints
        //       will be available in Symfony 5.1
        $ownerUsername = $input->getArgument('ownerUsername');

        /**
         * @var  User  $owner
         */
        $owner = $this->em->getRepository(User::class)->findOneBy([
            'username' => $ownerUsername
        ]);

        while (null === $ownerUsername && null === $owner) {
            $ownerUsername = $io->ask('Owner username', UserFixtures::USER_USERNAME);
            $owner = $this->em->getRepository(User::class)->findOneBy([
                'username' => $ownerUsername
            ]);
        }

        $projectName = $input->getArgument('projectName');

        $project = null;

        // TODO: Check the 2 following condition and correct them. Same for other file
        /**
         * @var  Project  $project
         */
        if (null !== $projectName) {
            $project = $this->em
                ->getRepository(Project::class)
                ->findOneByUserAndName($owner, $projectName);
        }

        while (null === $projectName && null === $project) {
            $projectName = $io->ask('Project name', ProjectFixtures::USER_PROJECT_NAME_1);
            /**
             * @var  Project  $project
             */
            $project = $this->em
                ->getRepository(Project::class)
                ->findOneByUserAndName($owner, $projectName);
        }

        $name = $input->getArgument('name');
        while (null === $name) {
            $name = $io->ask('Name', $this->faker->jobTitle);
        }

        $ok = $io->confirm(
            'This project will be created:' . PHP_EOL .
            ' <fg=blue>Owner:</> <fg=white>' . $ownerUsername . '</>' . PHP_EOL .
            ' <fg=blue>Project:</> <fg=white>' . $projectName . '</>' . PHP_EOL .
            ' <fg=blue>Name:</> <fg=white>' . $name . '</>' . PHP_EOL .
            ' Is everything correct ?'
        );

        if ($ok) {
            try {
                $this->em->persist(
                    (new Entity)
                        ->setName($name)
                        ->setProject($project)
                );
                $this->em->flush();
                $io->success(sprintf('The entity %s has been created', $name));
            } catch (Exception) {
                // $io->error($e->getMessage());
                $io->error('An error occurred, please try again');
            }
        } else {
            $io->error('Relaunch this command to try again');
        }

        return 0;
    }
}
