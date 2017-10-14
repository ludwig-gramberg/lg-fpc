<?php
namespace Lg;

use Lg\FullPageCache\Config;
use Lg\FullPageCache\Worker\CacheRefresh;

$bootstrapPath = rtrim(realpath(dirname(__FILE__).'/../'), '/').'/bootstrap.php';
require_once $bootstrapPath;

/** @var $config Config */

$worker = new CacheRefresh($config, 'cache-refresh', 3, LG_FPC_REVISION_FILE, 128, 14400); // max 128MiB, 4 hours
$worker->run();
$worker->shutdown();