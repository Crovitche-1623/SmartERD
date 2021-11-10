<?php

use App\Command\SetupCommand;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Run the app:setup command before running phpunit
$kernel = new Kernel('test', true);
$kernel->boot();
$application = new Application($kernel);
$command = new SetupCommand($kernel->getProjectDir(), $kernel);
$command->run(new ArrayInput([]), new ConsoleOutput());
