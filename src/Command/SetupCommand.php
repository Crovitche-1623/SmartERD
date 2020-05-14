<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{ArrayInput, InputInterface};
use Symfony\Component\Console\Output\{NullOutput, OutputInterface};
use Symfony\Component\Console\Style\SymfonyStyle;

final class SetupCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setName('app:setup')
            ->setDescription(<<<TXT
            Drop the existing database, create a new one, validate the schema,
            create the database schema and load the fixtures.
            TXT)
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('SmartERD - Installation assistant');
        $io->warning('Database suppression...');
        $returnCode = $this->getApplication()
            ->get('doctrine:database:drop')
            ->run(new ArrayInput([
                '--if-exists' => true,
                '--force' => true
            ]), new NullOutput)
        ;

        if (0 === $returnCode) {
            $io->success('The database has been deleted');

            $io->warning('Database creation...');
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:database:create')
                ->run(new ArrayInput([]), new NullOutput)
            ;
        }


        if (0 === $returnCode) {
            $io->success('The database has been created');

            $io->warning('Database schema creation...');
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:schema:create')
                ->run(new ArrayInput([]), new NullOutput)
            ;
        }

        if (0 === $returnCode) {
            $io->success('The database schema has been created');

            $io->warning('Adding test/dummy data...');
            $args = new ArrayInput([
                '--no-interaction' => true,
                '--purge-with-truncate' => true
            ]);
            $args->setInteractive(false);
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:fixtures:load')
                ->run($args, new NullOutput);
        }

        if (0 === $returnCode) {
            $io->success('The data has been created');

            $io->warning('Clearing Symfony cache...');
            $returnCode = $this
                ->getApplication()
                ->get('cache:clear')
                ->run(new ArrayInput([]), new NullOutput);
        }

        if (0 === $returnCode) {
            $io->success('The Symfony cache has been cleared');

            $io->warning('Clearing Doctrine Metadata cache...');
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:cache:clear-metadata')
                ->run(new ArrayInput([]), new NullOutput);
        }

        if (0 === $returnCode) {
            $io->success('The Doctrine Metadata cache has been cleared');

            $io->warning('Clearing Doctrine Query cache...');
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:cache:clear-query')
                ->run(new ArrayInput([]), new NullOutput);
        }

        if (0 === $returnCode) {
            $io->success('The Doctrine Query cache has been cleared');

            $io->warning('Clearing Doctrine Result cache...');
            $returnCode = $this
                ->getApplication()
                ->get('doctrine:cache:clear-result')
                ->run(new ArrayInput([]), new NullOutput);
        }

        if (0 === $returnCode) {
            $io->success('The Doctrine Result cache has been cleared');
        }

        return 0;
    }
}
