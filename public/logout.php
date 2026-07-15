<?php
declare(strict_types=1);

$services = require dirname(__DIR__) . '/config/bootstrap.php';

$auth = $services['auth'];

$auth->logout();

header('Location: /login.php');
exit;