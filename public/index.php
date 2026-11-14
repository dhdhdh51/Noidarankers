<?php
/**
 * NoidaRankers front controller. Every page request is routed through here.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

App\Core\App::run();
