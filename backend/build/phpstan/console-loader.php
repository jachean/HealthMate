<?php declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../../vendor/autoload.php';

$projectDir = dirname(__DIR__, 2);
$envFile = $projectDir . '/.env';

if (is_file($envFile)) {
    (new Dotenv())->bootEnv($envFile);
}

$kernel = new Kernel(
    $_SERVER['APP_ENV'] ?? 'dev',
    (bool) ($_SERVER['APP_DEBUG'] ?? true)
);

return new Application($kernel);
