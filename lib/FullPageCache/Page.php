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
	 * @param int $expireTime
	 * @param int $refreshInterval
	 */
	public function __construct($key, $body, array $headers, $expireTime = null, $refreshInterval = null) {
		$this->key             = $key;
		$this->body            = $body;
		$this->headers         = $headers;
		$this->expireTime      = $expireTime;
		$this->refreshInterval = $refreshInterval;
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
		if($this->expireTime !== null && $this->refreshInterval !== null) {
			$restOfAge = max(0, $this->expireTime-time());
			$expiresDate = gmdate('D, d M Y H:i:s \G\M\T', $this->expireTime);
			$lastModifiedDate = gmdate('D, d M Y H:i:s \G\M\T', $this->expireTime-$this->refreshInterval);
			header('Cache-Control:public, max-age='.$restOfAge.'', true);
			header('Expires:'.$expiresDate, true);
			header('Last-Modified:'.$lastModifiedDate, true);
			header_remove('Pragma');
		}
		foreach($this->headers as $header) {
			header($header, true);
		}
		echo $this->body;
	}
}