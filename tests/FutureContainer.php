<?php
namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\Assert\Assert;
use Wikimedia\JsonCodec\Hint;
use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;

/**
 * Sample container object which uses JsonCodecableTrait to directly implement
 * serialization/deserialization and uses the ONLY_FOR_DECODE class hint.
 */
class FutureContainer implements JsonCodecable {
	use JsonCodecableTrait;

	public string $type;

	/** @var mixed */
	public $contents;

	/**
	 * Create a new FutureContainer which stores $contents
	 * @param string $type
	 * @param mixed $contents
	 */
	public function __construct( string $type, $contents ) {
		$this->type = $type;
		$this->contents = $contents;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		$ret = [ 'type' => $this->type ];
		$ret[$this->type] = [ $this->contents ];
		return $ret;
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): self {
		$type = $json['type'];
		$arr = $json[$type];
		Assert::invariant(
			is_array( $arr ) && count( $arr ) === 1,
			"Ensure that the '$type' key is restored correctly"
		);
		return new FutureContainer( $type, $arr[0] );
	}

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ) {
		if ( $keyName === 'list1' ) {
			// Entire hint is "only for decode"
			return Hint::build( SampleObject::class, Hint::LIST, Hint::ONLY_FOR_DECODE );
		} elseif ( $keyName === 'list2' ) {
			// We can use the "list" hint now, but the exact type of list
			// elements is "only for decode"
			return Hint::build( SampleObject::class, Hint::ONLY_FOR_DECODE, Hint::LIST );
		} elseif ( $keyName === 'list3' ) {
			// Entire hint can be used for encode.
			return Hint::build( SampleObject::class, Hint::LIST );
		}
		return null;
	}
}
