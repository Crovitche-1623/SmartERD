<?php

declare(strict_types = 1);

namespace App\Command;

use App\DataFixtures\UserFixtures;
use App\Entity\{Project, User};
use Doctrine\ORM\EntityManagerInterface;
use Faker\{Factory,Generator};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ProjectCreateCommand extends Command
{
    private Generator $faker;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->faker = Factory::create('fr_CH');
        $this->em = $em;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('erd:project:create')
            ->setDescription('Create an project by asking few questions')
            ->addArgument('name', InputArgument::OPTIONAL, 'The project name')
            ->addArgument('ownerUsername', InputArgument::OPTIONAL, 'The owner username')
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
        $io->title('SmartERD - Project creator assistant');
        $io->text('Answer all the questions or press <return> to choose the [<comment>default value</comment>]');

        /**
         * TODO: Add validator to questions when Reusable sets of constraints
         *       will be available in Symfony 5.1
         */

        $name = $input->getArgument('name');
        while (null === $name) {
            $name = $io->ask('name', $this->faker->company);
        }

        $ownerUsername = $input->getArgument('ownerUsername');

        /**
         * @var  User  $owner
         */
        $owner = $this->em->getRepository(User::class)->findOneBy([
            'username' => $ownerUsername
        ]);

        while (null === $ownerUsername && $owner === null) {
            $ownerUsername = $io->ask('Owner username', UserFixtures::USER_USERNAME);
            $owner = $this->em->getRepository(User::class)->findOneBy([
                'username' => $ownerUsername
            ]);
        }

        $ok = $io->confirm(
            'This project will be created:' . PHP_EOL .
            ' <fg=blue>Name:</> <fg=white>' . $name . '</>' . PHP_EOL .
            ' <fg=blue>Owner:</> <fg=white>' . $ownerUsername . '</>' . PHP_EOL .
            ' Is everything correct ?'
        );

        if ($ok) {
            try {
                $this->em->persist(
                    (new Project)
                        ->setName($name)
                        ->setUser($owner)
                );
                $this->em->flush();
                $io->success(sprintf('The project %s has been created', $name));
            } catch (\Exception $e) {
                // $io->error($e->getMessage());
                $io->error('An error occurred, please try again');
            }
        } else {
            $io->error('Relaunch this command to try again');
        }

        return 0;
    }
}
