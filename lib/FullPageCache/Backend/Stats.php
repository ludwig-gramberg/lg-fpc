<?php
namespace Lg\FullPageCache\Backend;

class Stats {

	/**
	 * @var int
	 */
	protected $numberOfPages;

	/**
	 * @var int
	 */
	protected $memoryBytes;

	/**
	 * Stats constructor.
	 *
	 * @param int $numberOfPages
	 * @param int $memoryBytes
	 */
	public function __construct( $numberOfPages, $memoryBytes ) {
		$this->numberOfPages = $numberOfPages;
		$this->memoryBytes = $memoryBytes;
	}

	/**
	 * @return int
	 */
	public function getNumberOfPages() {
		return $this->numberOfPages;
	}

	/**
	 * @return int
	 */
	public function getMemoryBytes() {
		return $this->memoryBytes;
	}
}