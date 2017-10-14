<?php
namespace Lg;

use Lg\FullPageCache\Config;
use Lg\FullPageCache\Worker\CacheRefresh;

require_once '../bootstrap.php';
/** @var $config Config */

$worker = new CacheRefresh($config, 'cache-refresh', 3, LG_FPC_REVISION_FILE, 128, 14400); // max 128MiB, 4 hours
$worker->run();
$worker->shutdown();