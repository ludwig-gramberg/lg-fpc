<?php
namespace Lg\FullPageCache\Worker;

use Lg\FullPageCache\Backend;
use Lg\FullPageCache\Config;

class CacheRefresh extends AbstractWorker {

    const USER_AGENT = 'lg-fpc-cache-refresh-worker';

	/**
	 * @var Backend
	 */
    protected $backend;

	/**
	 * @var Config
	 */
    protected $config;

	/**
	 * @var int
	 */
    protected $parallelRequests = 8;

	/**
	 * @var int
	 */
    protected $parallelRequestsChunkSize = 32;

	/**
	 * @var int
	 */
    protected $expireInterval;

	/**
	 * @var resource
	 */
    protected $multiCurl;

	/**
	 * CacheRefresh constructor.
	 *
	 * @param Config $config
	 * @param string $name
	 * @param int|null $workInterval
	 * @param int|null $deploymentHashFile
	 * @param null $memoryLimit
	 * @param null $timeLimit
	 */
	public function __construct(Config $config, $name, $workInterval, $deploymentHashFile, $memoryLimit = null, $timeLimit = null ) {
		$this->backend = $config->getBackend();
		$this->config = $config;
		$this->expireInterval = $this->config->getExpireInterval();
		parent::__construct( $name, $workInterval, $deploymentHashFile, $memoryLimit, $timeLimit );
	}

	protected function init() {
		$this->multiCurl = curl_multi_init();
	}

	public function shutdown() {
		if($this->multiCurl) {
			curl_multi_close($this->multiCurl);
		}
	}

	protected function work() {

		// fetch list from backend
	    $requestKeys = $this->backend->getPagesToRefresh();

		// fetch metadata for pages
	    $pagesMetaData = $this->backend->getPagesMetaData($requestKeys);

		$requests = array();
		foreach($pagesMetaData as $pageMetaData) {
			$requests[$pageMetaData->requestKey] = $pageMetaData->url;
		}

		// break down list of requests into chunks
		// this prevents too many pages to be in memory at the same time
		$requestChunks = array_chunk($requests, $this->parallelRequestsChunkSize, true);
		foreach($requestChunks as $chunkOfRequests) {

			$results = $this->fetch($chunkOfRequests);

			foreach($results as $requestKey => $result) {
				list(
					$httpStatus,
					$returnData,
					$curlErrno,
					$curlError,
					$curlDebug
				) = $result;

				if($httpStatus == 404 || $httpStatus == 410 || $httpStatus == 301 || $httpStatus == 302) {
					// remove page from list for these statuses
					error_log('remove from cache '.$requestKey.' response: '.$httpStatus);
					$this->backend->removePage($requestKey);
					continue;
				}

				if($httpStatus <> 200 || $curlErrno != CURLE_OK) {
					error_log('cache fetch for '.$requestKey.' failed: '.$httpStatus.', error: '.$curlError.'('.$curlErrno.'), info: '.print_r($curlDebug, true));
					continue;
				}
				$pageMetaData = array_key_exists($requestKey, $pagesMetaData) ? $pagesMetaData[$requestKey] : null;
				if(!$pageMetaData) {
					continue;
				}
				$this->backend->storePage($requestKey, $pageMetaData->refreshInterval, $this->expireInterval, $returnData);
			}
		}
    }

	/**
	 * @param array $requests
	 * @return array
	 */
    protected function fetch(array $requests) {
	    if(empty($requests)) {
	    	return array();
	    }

	    $handles = array();
	    $fetchTimeout = $this->config->getCacheClientFetchTimeout();
	    $ignoreSslErrors = $this->config->isCacheClientIgnoreSslErrors();

	    foreach($requests as $p => $request) {
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $request);          // set URL
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);      // return received data on curl_exec()
		    curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
		    curl_setopt($ch, CURLOPT_TIMEOUT, $fetchTimeout);
			if($ignoreSslErrors) {
			    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}
		    $handles[$request] = array($ch, $p);
	    }

	    $i=0;
	    $keys = array_keys($requests);
	    $requestLength = count($requests);
	    while($i<$requestLength) {
		    for($h=0;$h<$this->parallelRequests;$h++) {
			    if($i<$requestLength) {
				    list($ch, ) = $handles[$requests[$keys[$i]]];
				    curl_multi_add_handle($this->multiCurl, $ch);
				    $i++;
			    }
		    }
		    $running = null;
		    do {
			    curl_multi_exec($this->multiCurl, $running);
			    usleep(100); // 1/10 ms
		    } while ($running > 0);
	    }

	    foreach($handles as $request => $set) {
		    list($ch, $p) = $set;

		    $returnData = curl_multi_getcontent($ch);
		    $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlErrno = curl_errno($ch);
			$curlError = curl_error($ch);
			$curlDebug = $curlErrno == CURLE_OK ? array() : curl_getinfo($ch);

		    $result = array(
			    $httpStatus,
			    $returnData,
			    $curlErrno,
				$curlError,
			    $curlDebug
		    );
		    $results[$p] = $result;
		    curl_multi_remove_handle($this->multiCurl, $ch);
	    }

	    return $results;
    }
}