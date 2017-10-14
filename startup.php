<?php
namespace Lg;

use Lg\FullPageCache\Request;
use Lg\FullPageCache\Worker\CacheRefresh;

// run request
$cache = FullPageCache::getInstance();

// here needs to be the authentication/detection logic for the cache client

$isCacheClient = $_SERVER['HTTP_USER_AGENT'] == CacheRefresh::USER_AGENT;
$cache->setIsCacheClient($isCacheClient);

$request = Request::getInstance();
$page = $cache->run($request);
if($page) {
	$page->sendResponse();
	exit;
}