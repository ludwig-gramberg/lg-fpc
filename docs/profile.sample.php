<?php
namespace Lg\FullPageCache;

// if you have a need for state here, start the session
session_start();

/** @var $config Config */

// this profile is shared in the codebase, it defines the cache behaviour
// it is deployed with the application code

// defines themes possible tags
$tagEnum = 0;
define('CACHE_TAG_ACCOUNT_SESSION_ACTIVE', 'CT'.(++$tagEnum)); // logged in
define('CACHE_TAG_ACCOUNT_SESSION_INACTIVE', 'CT'.(++$tagEnum)); // logged out

$tagEnum = 0;
define('PAGE_KEY_GENERIC', 'PK'.(++$tagEnum));
define('PAGE_KEY_HOME', 'PK'.(++$tagEnum));

$config->setDefaultResponseHeaders(array('Content-Type:text/html;charset=utf-8'));

// defines based on state whether or not to use the cache or not
// the page key is supplied to identify pages or groups of pages

$config->setUseCacheCallback(function($pageKey) {
	if($_SERVER['REQUEST_METHOD'] != 'GET') {
		return false;
	}
	// parameter to always bypass the cache for quick checks in production
	if(array_key_exists('nc', $_GET)) {
		return false;
	}
	// do not cache wordpress preview
	if(array_key_exists('preview', $_GET)) {
		return false;
	}
	return true;
});

// define post-processing of cache hit, which tags to keep and which to dispose of

$config->setProcessTagsCallback(function() {
	$tags = array();
	// from website account plugin
	if(array_key_exists('is_logged_in', $_SESSION)) {
		$tags[] = IFE_CACHE_TAG_ACCOUNT_SESSION_ACTIVE;
	} else {
		$tags[] = IFE_CACHE_TAG_ACCOUNT_SESSION_INACTIVE;
	}
	return $tags;
});

// post process the output before sending it to the client
// for anything else besides tags, like XSRF tokens, personalizations

$config->setPostProcessCallback(function(Page $page) {

	// here you have the chance to change headers
	// change the response body in any way shape or form

});