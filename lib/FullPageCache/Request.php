<?php
namespace Lg\FullPageCache;

class Request {

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var string
	 */
	protected $protocol;

	const PROTOCOL_HTTP = 'http';
	const PROTOCOL_HTTPS = 'https';

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * only parameter names, not values
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * @param string $domain
	 * @param string $protocol
	 * @param string $path
	 * @param array $params
	 */
	public function __construct($domain, $protocol, $path, array $params) {
		$domain = strtolower($domain);
		$this->domain   = $domain;
		$this->protocol = $protocol;
		$this->setPath($path);
		$this->setParams($params);
	}

	/**
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @return string
	 */
	public function getProtocol() {
		return $this->protocol;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @return bool
	 */
	public function hasParams() {
		return !empty($this->params);
	}

	/**
	 * @param array $params
	 */
	public function setParams($params) {
		sort($params);
		$this->params = $params;
	}

	/**
	 * @param string $path
	 */
	public function setPath( $path ) {
		$path = strtolower($path);
		$path = preg_replace('/[\/]+/', '/', $path);
		$path = preg_replace('/\?.*$/', '', $path);
		$this->path = $path;
	}

	/**
	 * example: https_www.domain.com_my-path-xyz
	 *
	 * @param bool $withParameters
	 *
	 * @return string
	 */
	public function getRequestKey($withParameters = true) {
		$requestKey = array();
		$requestKey[] = $this->protocol;
		$requestKey[] = $this->domain;
		$requestKey[] = str_replace('/', '-', trim($this->path, '/'));
		if($withParameters && !empty($this->params)) {
			$requestKey[] = implode(':', $this->params);
		}
		return implode('_', $requestKey);
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		$url = $this->protocol.'://'.$this->domain.$this->path;
		if(!empty($this->params)) {
			$url .= '?'.implode('&', $this->params);
		}
		return $url;
	}

	public static function getInstance() {
		return new Request(
			$_SERVER['HTTP_HOST'],
			array_key_exists('HTTPS', $_SERVER) ? Request::PROTOCOL_HTTPS : Request::PROTOCOL_HTTP,
			$_SERVER['REQUEST_URI'],
			array_keys($_GET)
		);
	}
}