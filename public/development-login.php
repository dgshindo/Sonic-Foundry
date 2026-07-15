<?php
declare(strict_types=1);

$services = require dirname(__DIR__) . '/config/bootstrap.php';

$auth = $services['auth'];
$userRepository = $services['userRepository'];

$email = 'development-user@example.com';

$user = $userRepository->findByEmail($email);

if (!$user) {
    throw new RuntimeException(
        'Development user not found. Run the repository test first.'
    );
}

$auth->login(
    user: $user,
    authenticationMethod: 'development',
);

header('Location: /workspace.php');
exit;