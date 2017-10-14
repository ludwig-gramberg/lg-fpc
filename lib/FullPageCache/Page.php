<?php
namespace Lg\FullPageCache;

class Page {

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var array
	 */
	protected $headers = array();

	/**
	 * @var int
	 */
	protected $expireTime;

	/**
	 * @var int
	 */
	protected $refreshInterval;

	/**
	 * Page constructor.
	 *
	 * @param string $key
	 * @param string $body
	 * @param array $headers
	 */
	public function __construct($key, $body, array $headers) {
		$this->key             = $key;
		$this->body            = $body;
		$this->headers         = $headers;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param string $body
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers) {
		$this->headers = $headers;
	}

	public function sendResponse() {
		header('X-FPC-Key:'.$this->key, true);
		foreach($this->headers as $header) {
			header($header, true);
		}
		echo $this->body;
	}
}