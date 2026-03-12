<?php declare(strict_types=1);

use Shadow\Framework\Application;

/** @var Application $application */
$application = require dirname(__FILE__, 2) . '/bootstrap/app.php';

$application->run();
