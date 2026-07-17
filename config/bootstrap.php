<?php
declare(strict_types=1);

use SonicFoundry\Application\Container;
use SonicFoundry\Auth\Session;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Load .env
|--------------------------------------------------------------------------
*/

$envPath = dirname(__DIR__) . '/.env';

if (!is_file($envPath)) {
    throw new RuntimeException(
        '.env file not found.'
    );
}

$lines = file(
    $envPath,
    FILE_IGNORE_NEW_LINES
    | FILE_SKIP_EMPTY_LINES
);

foreach ($lines as $line) {

    $line = trim($line);

    if (
        $line === ''
        || str_starts_with($line, '#')
    ) {
        continue;
    }

    [
        $name,
        $value
    ] = array_pad(
        explode('=', $line, 2),
        2,
        ''
    );

    $name = trim($name);
    $value = trim($value);

    if (
        strlen($value) >= 2
        && (
            (
                $value[0] === '"'
                && $value[-1] === '"'
            )
            || (
                $value[0] === "'"
                && $value[-1] === "'"
            )
        )
    ) {
        $value = substr(
            $value,
            1,
            -1
        );
    }

    $_ENV[$name] = $value;

    putenv(
        $name . '=' . $value
    );
}

/*
|--------------------------------------------------------------------------
| Environment helper
|--------------------------------------------------------------------------
*/

function env(
    string $key,
    ?string $default = null
): ?string {
    $value =
        $_ENV[$key]
        ?? getenv($key);

    if (
        $value === false
        || $value === null
        || $value === ''
    ) {
        return $default;
    }

    return (string) $value;
}

/*
|--------------------------------------------------------------------------
| Start session first
|--------------------------------------------------------------------------
*/

Session::start();

/*
|--------------------------------------------------------------------------
| Build application container
|--------------------------------------------------------------------------
*/

$container = new Container();

$auth = $container->auth();

$userRepository = $container->users();

return [
    'container' => $container,
    'auth' => $auth,
    'userRepository' => $userRepository,
];