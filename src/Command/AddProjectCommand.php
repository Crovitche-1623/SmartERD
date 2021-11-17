<?php

declare(strict_types=1);

namespace App\Command;

use App\DataFixtures\UserFixtures;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddProjectCommand extends Command
{
    // to make your command lazily loaded, configure the $defaultName static property,
    // so it will be instantiated only when the command is actually called.
    /** @var  string  $defaultName */
    protected static $defaultName = 'erd:project:add';

    private SymfonyStyle $io;
    private Generator $faker;

    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Create a project and store it in the database')
            ->setHelp(self::getCommandHelp())
            ->addArgument(
                name:'name',
                mode: InputArgument::OPTIONAL,
                description: 'The project name'
            )
            ->addArgument(
                name: 'owner-username',
                mode: InputArgument::OPTIONAL,
                description: 'The project owner username'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
        $this->faker = Factory::create('fr_CH');
    }

    /**
     * {@inheritDoc}
     */
    protected function interact(
        InputInterface $input,
        OutputInterface $output
    ): void
    {
        /** @var  string|null  $name */
        $name = $input->getArgument('name');

        /** @var  string|null  $ownerUsername */
        $ownerUsername = $input->getArgument('owner-username');

        /** @var  User|null  $owner */
        $owner = $this->em->getRepository(User::class)->findOneBy([
            'username' => $ownerUsername
        ]);

        $project = (new Project)
            ->setName($name)
            ->setUser($owner);

        $errors = $this->validator->validate($project);

        if (count($errors) > 0) {
            $this->io->title('SmartERD - Project creator assistant');
            $this->io->text(
                'Some values were not provided / missing or invalid.'
            );
            $this->io->text(
                'Answer all the questions or press <return> to choose the '.
                '[<comment>default or previous value</comment>]'
            );
        }

        while ($errors->count() > 0) {
            /** @var  string|null  $name */
            $name = $this->io->ask(
                question: 'Name',
                default: $name ?: $this->faker->company()
            );

            $project->setName($name);

            /** @var  string|null  $ownerUsername */
            $ownerUsername = $this->io->ask(
                question: 'Owner Username',
                default: $ownerUsername ?: UserFixtures::USER_USERNAME
            );

            /** @var  User|null  $owner */
            $owner = $this->em->getRepository(User::class)->findOneBy([
                'username' => $ownerUsername
            ]);

            $project->setUser($owner);

            $errors = $this->validator->validate($project);

            /** @var  ConstraintViolationInterface  $error */
            foreach ($errors as $error) {
                $this->io->error($error->getPropertyPath() . ' ' .$error->getMessage());
            }
        }

        $input->setArgument('name', $name);
        $input->setArgument('owner-username', $ownerUsername);
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     *
     * {@inheritDoc}
     *
     * @return  Command::SUCCESS|Command::INVALID|Command::FAILURE
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start(self::$defaultName);

        /** @var  string  $name */
        $name = $input->getArgument('name');

        /** @var  string  $ownerUsername */
        $ownerUsername = $input->getArgument('owner-username');

        /** @var  User|null  $owner */
        $owner = $this->em->getRepository(User::class)->findOneBy([
            'username' => $ownerUsername
        ]);

        $project = (new Project)
            ->setName($name)
            ->setUser($owner);

        $this->em->persist($project);

        $errors = $this->validator->validate($project);

        if (count($errors) > 0) {
            /** @var  ConstraintViolationInterface  $error */
            foreach ($errors as $error) {
                $this->io->error($error->getPropertyPath() . ' ' .$error->getMessage());
            }

            $this->io->error('The user has not been created.');

            return Command::FAILURE;
        }

        $this->em->flush();

        $this->io->text(sprintf(
            '<fg=green>This project has been created:</>' . PHP_EOL .
            ' <fg=blue>Slug:</> <fg=white>%s</>'. PHP_EOL .
            ' <fg=blue>Name:</> <fg=white>%s</>'. PHP_EOL .
            ' <fg=blue>Owner:</> <fg=white>%s</>',
            $project->getSlug(),
            $project->getName(),
            $ownerUsername
        ));

        $event = $stopwatch->stop(self::$defaultName);

        if ($output->isVerbose()) {
            $this->io->comment(sprintf(<<<TXT
Elapsed time: %.2f ms / Consumed memory: %.2f MB'
TXT
            , $event->getDuration(),
              $event->getMemory()
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * The command help is usually included in the "configure()" method, but
     * when it's too long, it's better to define a separate method to maintain
     * the code readability.
     */
    private static function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%<info> command create a new project and save it in the
database:

  <info>php %command.full_name%</info> <comment>name owner-username</comment>

If you omit any of the two required arguments, the command will ask you to
provide the missing values:

  # command will ask you for the name
  <info>php %command.full_name%</info> <comment>owner-username</comment>

  # command will ask you for the owner-username
  <info>php %command.full_name%</info> <comment>name</comment>

  # command wil ask you for all arguments
  <info>php %command.full_name%</info>
HELP;
    }
}
