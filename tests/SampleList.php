<?php
namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;

/**
 * Sample object which encodes itself as a list.
 */
class SampleList implements JsonCodecable {
	use JsonCodecableTrait;

	private array $list;

	/**
	 * Create a new SampleList object.
	 * @param int ...$list
	 */
	public function __construct( ...$list ) {
		$this->list = $list;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return $this->list;
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): SampleList {
		return new SampleList( ...$json );
	}
}
