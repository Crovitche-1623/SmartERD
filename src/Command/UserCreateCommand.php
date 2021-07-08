<?php

declare(strict_types = 1);

namespace App\Command;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\{Factory,Generator};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UserCreateCommand extends Command
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
            ->setName('app:user:create')
            ->setDescription('Create an user by asking few questions')
            ->addArgument('username', InputArgument::OPTIONAL, 'The user username')
            ->addArgument('email', InputArgument::OPTIONAL, 'The user email')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'If the user is admin or not')
            ->addArgument('plainPassword', InputArgument::OPTIONAL, 'The plain password (not hashed)')
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
        $io->title('SmartERD - User creator assistant');
        $io->text('Answer all the questions or press <return> to choose the [<comment>default value</comment>]');

        /**
         * TODO: Add validator to questions when Reusable sets of constraints
         *       will be available in Symfony 5.1
         */

        $username = $input->getArgument('username');
        while (null === $username) {
            $username = $io->ask('Username', $this->faker->userName);
        }

        $email = $input->getArgument('email');
        while (null === $email) {
            $email = $io->ask('Email', $this->faker->freeEmail);
        }

        $isAdmin = $input->getArgument('isAdmin');
        while (!is_bool($isAdmin)) {
            $isAdmin = $io->confirm('Is the user an administrator ?', false);
        }

        $plainPassword = $input->getArgument('plainPassword');
        while (null === $plainPassword) {
            $plainPassword = $io->ask('Password', UserFixtures::DEFAULT_USER_PASSWORD);
        }

        $ok = $io->confirm(
            'This user will be created:' . PHP_EOL .
            ' <fg=blue>Username:</> <fg=white>'         . $username                 . '</>' . PHP_EOL .
            ' <fg=blue>Email:</> <fg=white>'            . $email                    . '</>' . PHP_EOL .
            ' <fg=blue>Is administrator:</> <fg=white>' . ($isAdmin ? 'Yes' : 'No') . '</>' . PHP_EOL .
            ' <fg=blue>Plain password:</> <fg=white>'   . $plainPassword            . '</>' . PHP_EOL .
            ' Is everything correct ?'
        );

        if ($ok) {
            try {
                $this->em->persist(
                    (new User)
                        ->setUsername($username)
                        ->setEmail($email)
                        ->setIsAdmin($isAdmin)
                        ->setPlainPassword($plainPassword)
                );
                $this->em->flush();
                $io->success(sprintf('The user %s has been created', $username));
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
