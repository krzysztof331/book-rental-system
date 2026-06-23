<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

$run = static function (array $command) use ($application): void {
    $application->run(new ArrayInput($command), new NullOutput());
};

$run(['command' => 'doctrine:database:create', '--if-not-exists' => true]);
$run(['command' => 'doctrine:schema:drop', '--full-database' => true, '--force' => true]);
$run(['command' => 'doctrine:schema:create']);

$kernel->shutdown();
