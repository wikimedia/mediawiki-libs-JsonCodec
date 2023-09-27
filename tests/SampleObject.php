<?php
namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\Assert\Assert;
use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;

/**
 * Sample object which uses JsonCodecableTrait to directly implement
 * serialization/deserialization.
 */
class SampleObject implements JsonCodecable {
	use JsonCodecableTrait;

	/** @var string */
	public string $property;

	/**
	 * Create a new SampleObject which stores $property.
	 * @param string $property
	 */
	public function __construct( string $property ) {
		$this->property = $property;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		if ( $this->property === 'suppress _type_' ) {
			// Allow testing both with and without the '_type_' special case
			return [ 'property' => $this->property ];
		}
		return [
			'property' => $this->property,
			// Implementers shouldn't have to know which properties the
			// codec is using for its own purposes; this will still work
			// fine:
			'_type_' => 'check123',
		];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): SampleObject {
		if ( $json['property'] !== 'suppress _type_' ) {
			Assert::invariant( $json['_type_'] === 'check123', 'protected field' );
		}
		return new SampleObject( $json['property'] );
	}
}
