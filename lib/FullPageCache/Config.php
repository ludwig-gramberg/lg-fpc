<?php
namespace Lg\FullPageCache;

class Config {

	/**
	 * @var int
	 */
	protected $defaultRefreshInterval;

	/**
	 * how old can something get after it should have been refreshed before it is removed from redis
	 * so if the refresh worker was not working everything would be removed from redis (produce misses)
	 * after this time
	 *
	 * @var int
	 */
	protected $expireInterval;

	/**
	 * @var int
	 */
	protected $cacheClientFetchTimeout;

	/**
	 * @var bool
	 */
	protected $cacheClientIgnoreSslErrors = false;

	/**
	 * @var array
	 */
	protected $defaultResponseHeaders = array();

	/**
	 * @var array
	 */
	protected $domains = array();

	/**
	 * @var array
	 */
	protected $protocols = array();

	/**
	 * @var array
	 */
	protected $stateTags = array();

	/**
	 * @var callable
	 */
	protected $useCacheCallback;

	/**
	 * @var callable
	 */
	protected $processTagsCallback;

	/**
	 * @var callable
	 */
	protected $postProcessCallback;

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * Config constructor.
	 *
	 * @param Backend $backend
	 * @param array $domains
	 * @param array $protocols
	 * @param int $defaultRefreshInterval
	 * @param int $expireInterval
	 * @param int $cacheClientFetchTimeout
	 */
	public function __construct(Backend $backend, array $domains, array $protocols, $defaultRefreshInterval = 600, $expireInterval = 600, $cacheClientFetchTimeout = 30) {
		$this->backend = $backend;
		$this->domains = $domains;
		$this->protocols = $protocols;
		$this->defaultRefreshInterval = $defaultRefreshInterval;
		$this->expireInterval = $expireInterval;
		$this->cacheClientFetchTimeout = $cacheClientFetchTimeout;
	}

	/**
	 * @return bool
	 */
	public function isCacheClientIgnoreSslErrors() {
		return $this->cacheClientIgnoreSslErrors;
	}

	/**
	 * @param bool $cacheClientIgnoreSslErrors
	 */
	public function setCacheClientIgnoreSslErrors( $cacheClientIgnoreSslErrors ) {
		$this->cacheClientIgnoreSslErrors = $cacheClientIgnoreSslErrors;
	}

	/**
	 * @return Backend
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * @param Backend $backend
	 */
	public function setBackend(Backend $backend) {
		$this->backend = $backend;
	}

	/**
	 * @return array
	 */
	public function getDomains() {
		return $this->domains;
	}

	/**
	 * @param array $domains
	 */
	public function setDomains(array $domains) {
		$this->domains = $domains;
	}

	/**
	 * @return array
	 */
	public function getProtocols() {
		return $this->protocols;
	}

	/**
	 * @param array $protocols
	 */
	public function setProtocols(array $protocols) {
		$this->protocols = $protocols;
	}

	/**
	 * @return array
	 */
	public function getStateTags() {
		return $this->stateTags;
	}

	/**
	 * @param array $stateTags
	 */
	public function setStateTags(array $stateTags) {
		$this->stateTags = $stateTags;
	}

	/**
	 * @param string $stateTag
	 */
	public function addStateTag($stateTag) {
		$this->stateTags[] = $stateTag;
	}

	/**
	 * @param callable $useCacheCallback
	 */
	public function setUseCacheCallback(callable $useCacheCallback) {
		$this->useCacheCallback = $useCacheCallback;
	}

	/**
	 * @return bool
	 */
	public function hasUseCacheCallback() {
		return is_callable($this->useCacheCallback);
	}

	/**
	 * @return callable
	 */
	public function getUseCacheCallback() {
		return $this->useCacheCallback;
	}

	/**
	 * @return callable
	 */
	public function getProcessTagsCallback() {
		return $this->processTagsCallback;
	}

	/**
	 * @return bool
	 */
	public function hasProcessTagsCallback() {
		return is_callable($this->processTagsCallback);
	}

	/**
	 * @param callable $processTagsCallback
	 */
	public function setProcessTagsCallback(callable $processTagsCallback) {
		$this->processTagsCallback = $processTagsCallback;
	}

	/**
	 * @return callable
	 */
	public function getPostProcessCallback() {
		return $this->postProcessCallback;
	}

	/**
	 * @return bool
	 */
	public function hasPostProcessCallback() {
		return is_callable($this->postProcessCallback);
	}

	/**
	 * @param callable $postProcessCallback
	 */
	public function setPostProcessCallback(callable $postProcessCallback) {
		$this->postProcessCallback = $postProcessCallback;
	}

	/**
	 * @return int
	 */
	public function getDefaultRefreshInterval() {
		return $this->defaultRefreshInterval;
	}

	/**
	 * @param array $defaultResponseHeaders
	 */
	public function setDefaultResponseHeaders(array $defaultResponseHeaders) {
		$this->defaultResponseHeaders = $defaultResponseHeaders;
	}

	/**
	 * @return array
	 */
	public function getDefaultResponseHeaders() {
		return $this->defaultResponseHeaders;
	}

	/**
	 * @return int
	 */
	public function getExpireInterval() {
		return $this->expireInterval;
	}

	/**
	 * @return int
	 */
	public function getCacheClientFetchTimeout() {
		return $this->cacheClientFetchTimeout;
	}

}