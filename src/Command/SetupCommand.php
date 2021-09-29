<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{ArrayInput, InputInterface};
use Symfony\Component\Console\Output\{NullOutput, OutputInterface};
use Symfony\Component\Console\Style\SymfonyStyle;

final class SetupCommand extends Command
{
    protected static $defaultName = 'app:setup';
    private string $jwtConfigDir;

    public function __construct(string $projectDirectory)
    {
        parent::__construct(self::$defaultName);
        $this->jwtConfigDir = $projectDirectory . '/config/jwt';
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setName(self::$defaultName)
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
        // TODO: Add Lock to avoid concurrent run
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
            $privateKey = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 4096
            ]);

            $privateKeyFileName = $this->jwtConfigDir . '/private.pem';

            openssl_pkey_export_to_file($privateKey, $privateKeyFileName);

            $publicKey = openssl_pkey_get_details($privateKey)['key'];

            file_put_contents($this->jwtConfigDir . '/public.pem', $publicKey);

            $returnCode = 0;
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
            if (function_exists('apcu_clear_cache')) {
                $io->warning('Clearing APC cache...');
                apcu_clear_cache();

                $io->success('APCu cache has been cleared');
            }
        }

        return 0;
    }
}
