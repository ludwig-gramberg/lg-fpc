<?php
namespace Lg;

define('LG_FPC_BASE_PATH', rtrim(dirname(__FILE__), '/').'/');
define('LG_FPC_CONFIG_PATH', rtrim(realpath(dirname(__FILE__).'/../../../lg-fpc-config'), '/').'/');
define('LG_FPC_COMPOSER_PATH', rtrim(realpath(dirname(__FILE__).'/../../../composer'), '/').'/');
define('LG_FPC_REVISION_FILE', realpath(dirname(__FILE__).'/../../../.revision'));

require_once LG_FPC_COMPOSER_PATH.'vendor/autoload.php';

spl_autoload_register(function($className) {

	if(strpos($className, 'Lg\\FullPageCache') !== 0) {
		return;
	}

	$className = substr($className, 3);
	$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	$filePath = LG_FPC_BASE_PATH.'lib/'.$fileName.'.php';

	require_once $filePath;

}, true, true);

require_once LG_FPC_CONFIG_PATH.'config.php';
require_once LG_FPC_CONFIG_PATH.'profile.php';

/** @var $config Config */

FullPageCache::createInstance($config);