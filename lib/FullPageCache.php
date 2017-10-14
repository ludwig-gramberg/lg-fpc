<?php
namespace Lg;

use Lg\FullPageCache\Config;
use Lg\FullPageCache\Request;
use Lg\FullPageCache\Page;
use Lg\FullPageCache\Backend\Stats;

class FullPageCache {

	/**
	 * @var FullPageCache
	 */
	protected static $instance;

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * @var bool
	 */
	protected $isCacheClient = false;

	/**
	 * @var array
	 */
	protected $renderTags = array();

	/**
	 * @param Config $config
	 */
	protected function __construct(Config $config) {
		$this->config = $config;
		$this->backend = $config->getBackend();
		if($this->config->hasProcessTagsCallback()) {
			$this->renderTags = call_user_func($this->config->getProcessTagsCallback());
		}
	}

	/**
	 * @param Config $config
	 */
	public static function createInstance(Config $config) {
		if(!self::$instance) {
			self::$instance = new FullPageCache($config);
		}
	}

	/**
	 * @param bool $isCacheClient
	 */
	public function setIsCacheClient($isCacheClient) {
		$this->isCacheClient = $isCacheClient;
	}

	/**
	 * @return FullPageCache
	 */
	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * @param Request $request
	 * @return Page
	 */
	public function run(Request $request) {

		// allowed protocols
		$allowedProtocols = $this->config->getProtocols();
		if(!in_array($request->getProtocol(), $allowedProtocols)) {
			return null;
		}

		// allowed host names
		$allowedHostNames = $this->config->getDomains();
		if(!in_array($request->getDomain(), $allowedHostNames)) {
			return null;
		}

		if($request->hasParams()) {

			// prepare parameters
			$requestParams = $request->getParams();

			// determine relevant params and extend request key
			$requestKey = $request->getRequestKey(false);
			$paramsOptions = $this->backend->getParamOptions($requestKey);

			// should the parameter information be missing in the cache we can not proceed
			// otherwise possible parameter combinations would result in delivering the base page without parameters, instead
			// this must be treated as a cache miss
			if($paramsOptions === null) {
				return null;
			}
			// filter out a possible allows parameter combination and apply it to the request
			$paramsOption = $this->getParamsOptionMatch($paramsOptions, $requestParams);
			$request->setParams($paramsOption);
		}
		$requestKey = $request->getRequestKey(true);
		$page = $this->backend->getPage($requestKey);
		if(!$page) {
			// cache miss? we're finished here, let wp continue
			return null;
		}
		// cache hit

		// use cache callback
		if($this->config->hasUseCacheCallback()) {
			$useCache = call_user_func($this->config->getUseCacheCallback(), $page->getKey());
			if(!$useCache) {
				return null;
			}
		}

		// tags callback
		$this->renderTags($page);

		// post processing
		if($this->config->hasPostProcessCallback()) {
			call_user_func($this->config->getPostProcessCallback(), $page);
		}

		return $page;
	}

	/**
	 * @param array $paramsOptions
	 * @param array $requestParams
	 * @return array
	 */
	protected function getParamsOptionMatch(array $paramsOptions, array $requestParams) {

		// all param permutations sorted alphabetically
		array_walk($paramsOptions, function(&$params) { sort($params); });
		// longest lists first
		usort($paramsOptions, function($a, $b) { return count($b)-count($a); });

		foreach($paramsOptions as $paramsOption) {
			$matched = true;
			foreach($paramsOption as $paramOption) {
				if(!in_array($paramOption, $requestParams)) {
					// if param option is not in request, not a match, try next
					$matched = false;
					break;
				}
			}
			if($matched) {
				return $paramsOption;
				break;
			}
		}
		return array();
	}

	/**
	 * @param Page $page
	 */
	protected function renderTags(Page $page) {
		$body = $page->getBody();
		preg_match_all('/\<!\-\-FPC:(.+)\-\-\>/U', $body, $m);
		foreach($m[1] as $bodyTag) {
			if(in_array($bodyTag, $this->renderTags)) {
				$body = preg_replace('/\<!\-\-(\/)?FPC:'.$bodyTag.'\-\-\>/', '', $body);
			} else {
				$body = preg_replace('/\<!\-\-FPC:'.$bodyTag.'\-\-\>(.*?)\<!\-\-\/FPC:'.$bodyTag.'\-\-\>/sU', '', $body);
			}
		}
		$page->setBody($body);
	}

	/**
	 * @param string $tag
	 * @param callable $contentCallback
	 */
	public function renderTag($tag, callable $contentCallback) {
		$renderTags = $this->isCacheClient;
		$renderContent = $renderTags || in_array($tag, $this->renderTags);
		if($renderTags) {
			echo '<!--FPC:'.$tag.'-->';
		}
		if($renderContent) {
			call_user_func($contentCallback);
		}
		if($renderTags) {
			echo '<!--/FPC:'.$tag.'-->';
		}
	}

	/**
	 * registers a page to be handled by the cache
	 *
	 * @param string $pageKey
	 * @param int $refreshInterval
	 * @param array $paramsOptions
	 * @param array $responseHeaders
	 */
	public function registerPage($pageKey, $refreshInterval = null, array $paramsOptions = array(), array $responseHeaders = null) {

		$refreshInterval = $refreshInterval ? $refreshInterval : $this->config->getDefaultRefreshInterval();
		$responseHeaders = $responseHeaders ? $responseHeaders : $this->config->getDefaultResponseHeaders();

		$request = Request::getInstance();
		$requestParams = $request->getParams();
		$paramsOption = $this->getParamsOptionMatch($paramsOptions, $requestParams);
		$request->setParams($paramsOption);

		$this->backend->registerPage( $request, $pageKey, $refreshInterval, $paramsOptions, $responseHeaders);
	}

	/**
	 * @return array
	 */
	public function getPagesToRefresh() {
		$expireInterval = $this->config->getExpireInterval();
		$pageList = $this->backend->getPagesToRefresh($expireInterval);
		return $pageList;
	}

	public function flush() {
		$this->backend->flush();
	}

	public function refreshAll() {
		$this->backend->refreshAll();
	}

	/**
	 * @return Stats
	 */
	public function getStats() {
		return $this->backend->getStats();
	}
}