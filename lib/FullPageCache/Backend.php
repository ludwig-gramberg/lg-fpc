<?php
namespace Lg\FullPageCache;

use Lg\FullPageCache\Backend\Stats;

class Backend {

	/**
	 * @var string
	 */
	protected $redisHost;

	/**
	 * @var string
	 */
	protected $redisPort;

	/**
	 * @var string
	 */
	protected $redisAuth;

	/**
	 * @var float
	 */
	protected $redisTimeout;

	/**
	 * @var \Credis_Client
	 */
	protected $redisConnection;

	/**
	 * @var int
	 */
	protected $bzCompressionLevel;

	/**
	 * @var int
	 */
	protected $minCompressionByteSize;

	/**
	 * @var int
	 */
	protected $jsonOptions;

	const COMPRESSION_TYPE_GZIP = 'gz';
	const COMPRESSION_TYPE_NONE = 'rv';

	/**
	 * @param string $redisHost
	 * @param int $redisPort
	 * @param float $redisTimeout
	 * @param null $redisAuth
	 * @param int $bzCompressionLevel
	 * @param int $minCompressionByteSize
	 */
	public function __construct($redisHost = '127.0.0.1', $redisPort = 6379, $redisTimeout = 1.0, $redisAuth = null, $bzCompressionLevel = 7, $minCompressionByteSize = 2048) {

		$this->redisHost = $redisHost;
		$this->redisPort = $redisPort;
		$this->redisAuth = $redisAuth;
		$this->redisTimeout = $redisTimeout;
		$this->jsonOptions = JSON_UNESCAPED_SLASHES;

		$this->bzCompressionLevel = $bzCompressionLevel;
		$this->minCompressionByteSize = $minCompressionByteSize;
	}

	/**
	 * @return \Credis_Client
	 */
	protected function getConnection() {
		if(!$this->redisConnection) {
			$this->redisConnection = new \Credis_Client($this->redisHost, $this->redisPort, $this->redisTimeout, '', 0, $this->redisAuth);
		}
		return $this->redisConnection;
	}

	/**
	 * returns possible permutations of parameters for a request
	 *
	 * @param $requestKey
	 * @return array|null
	 */
	public function getParamOptions($requestKey) {
		try {
			$connection = $this->getConnection();
			$paramsCacheKey = self::CACHE_KEY_PARAMS_.$requestKey;

			$paramsDataJson = $connection->get($paramsCacheKey);

			if($paramsDataJson) {
				$paramsOptions = json_decode($paramsDataJson);
				if(is_array($paramsOptions)) {
					return $paramsOptions;
				}
			}

		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return null;
	}

	/**
	 * @param $requestKey
	 * @return Page
	 */
	public function getPage($requestKey) {
		try {
			$connection = $this->getConnection();
			$pageCacheKey = self::CACHE_KEY_PAGE_.$requestKey;

			$pageData = $connection->get($pageCacheKey);
			if(!$pageData) {
				return null;
			}

			$pageMetaDataJson = $connection->hGet(self::CACHE_KEY_LIST, $requestKey);
			if(!$pageMetaDataJson) {
				return null;
			}
			$pageMetaData = json_decode($pageMetaDataJson);
			if(!is_object($pageMetaData) || !($pageMetaData instanceof \stdClass)) {
				return null;
			}

			$compressionType = substr($pageData, 0, 2);
			$pageData = substr($pageData, 3);
			if($compressionType == self::COMPRESSION_TYPE_GZIP) {
				$uncompressedPageData = gzuncompress($pageData);
				if($uncompressedPageData === false) {
					return null;
				}
				$pageData = $uncompressedPageData;
				unset($uncompressedPageData);
			}

			$expireTime = intval($connection->zScore(self::CACHE_KEY_QUEUE, $requestKey));
			$expireTime = $expireTime > 0 ? $expireTime : null;

			$page = new Page($pageMetaData->pageKey, $pageData, $pageMetaData->responseHeaders, $expireTime, $pageMetaData->refreshInterval);
			return $page;

		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return null;
	}

	const CACHE_KEY_LIST    = 'list';
	const CACHE_KEY_QUEUE   = 'queue';
	const CACHE_KEY_PARAMS_ = 'params_';
	const CACHE_KEY_PAGE_   = 'page_';

	/**
	 * @param Request $request
	 * @param string $pageKey
	 * @param int $refreshInterval
	 * @param array $paramsOptions
	 * @param array $responseHeaders
	 */
	public function registerPage(Request $request, $pageKey, $refreshInterval, array $paramsOptions, array $responseHeaders) {
		try {
			$connection = $this->getConnection();
			$requestKey = $request->getRequestKey();

			$queueData = new \stdClass();
			$queueData->url = $request->getUrl();
			$queueData->refreshInterval = $refreshInterval;
			$queueData->pageKey = $pageKey;
			$queueData->requestKey = $requestKey;
			$queueData->responseHeaders = $responseHeaders;

			$queueDataJson = json_encode($queueData, $this->jsonOptions);

			/**
			 * Adds all the specified members with the specified scores to the sorted set stored at key.
			 * If a specified member is already a member of the sorted set, the score is updated and the element reinserted at the right position to ensure the correct ordering.
			 */
			$connection->zAdd(self::CACHE_KEY_QUEUE, 0, $requestKey);

			/**
			 * Sets field in the hash stored at key to value, only if field does not yet exist.
			 * If key does not exist, a new key holding a hash is created.
			 * If field already exists, this operation has no effect.
			 * https://redis.io/commands/hsetnx
			 */
			$connection->hSetNx(self::CACHE_KEY_LIST, $requestKey, $queueDataJson);

			$paramsCacheKey = self::CACHE_KEY_PARAMS_.$request->getRequestKey(false);
			$paramsDataJson = json_encode($paramsOptions, $this->jsonOptions);

			/**
			 * we store all possible parameter combinations for each base-request (without params)
			 * so later we can quickly look them up
			 */
			$connection->set($paramsCacheKey, $paramsDataJson);

		} catch(\Exception $e) {
			error_log((string)$e);
		}
	}

	/**
	 * @param string $requestKey
	 * @param int $refreshInterval
	 * @param int $expireInterval
	 * @param string $responseBody
	 */
	public function storePage($requestKey, $refreshInterval, $expireInterval, $responseBody) {
		try {
			$connection = $this->getConnection();

			/**
			 * Sets field in the hash stored at key to value, only if field does not yet exist.
			 * If key does not exist, a new key holding a hash is created.
			 * If field already exists, this operation has no effect.
			 * https://redis.io/commands/hsetnx
			 */
			$cacheKey = self::CACHE_KEY_PAGE_.$requestKey;

			$storeResponseBody = self::COMPRESSION_TYPE_NONE.':'.$responseBody; // rv: raw value
			if(strlen($responseBody) > $this->minCompressionByteSize) { // don't compress if too small
				$compressedResponseBody = gzcompress($responseBody, $this->bzCompressionLevel);
				if($compressedResponseBody !== false) {
					$storeResponseBody = self::COMPRESSION_TYPE_GZIP.':'.$compressedResponseBody; // gz: compressed
				}
			}

			$connection->set($cacheKey, $storeResponseBody);
			$connection->expire($cacheKey, $refreshInterval+$expireInterval);
			$connection->zAdd(self::CACHE_KEY_QUEUE, time()+$refreshInterval, $requestKey);

		} catch(\Exception $e) {
			error_log((string)$e);
		}
	}

	/**
	 * @return array
	 */
	public function getPagesToRefresh() {
		try {
			$connection = $this->getConnection();
			$pageList = $connection->zRangeByScore(self::CACHE_KEY_QUEUE, 0, time());
			if(is_array($pageList)) {
				return $pageList;
			}
		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return array();
	}

	/**
	 * @param array $requestKeys
	 * @return array
	 */
	public function getPagesMetaData(array $requestKeys) {
		if(empty($requestKeys)) {
			return array();
		}
		try {
			$connection = $this->getConnection();
			$pagesMetaDataJson = $connection->hMGet(self::CACHE_KEY_LIST, $requestKeys);
			if(is_array($pagesMetaDataJson)) {
				$pages = array();
				foreach($pagesMetaDataJson as $pageMetaDataJson) {
					$pageMetaData = json_decode($pageMetaDataJson);
					if($pageMetaData && $pageMetaData instanceof \stdClass) {
						$pages[$pageMetaData->requestKey] = $pageMetaData;
					}
				}
				return $pages;
			}
		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return array();
	}

	/**
	 * flush entire cache
	 */
	public function flush() {
		try {
			$connection = $this->getConnection();
			$connection->flushAll();
		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return array();
	}

	/**
	 * set all pages to be refreshed by the cache worker
	 */
	public function refreshAll() {
		try {
			$connection = $this->getConnection();
			$keys = $connection->hKeys(self::CACHE_KEY_LIST);
			foreach($keys as $key) {
				$connection->zAdd(self::CACHE_KEY_QUEUE, 0, $key);
			}
		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return array();
	}

	/**
	 * @return Stats
	 */
	public function getStats() {
		try {
			$connection = $this->getConnection();

			$memorySection = $connection->info('memory');
			$memory = intval($memorySection['used_memory']);

			$keys = $connection->hKeys(self::CACHE_KEY_LIST);

			$stats = new Stats(count($keys), $memory);
			return $stats;

		} catch(\Exception $e) {
			error_log((string)$e);
		}
		return array();
	}
}