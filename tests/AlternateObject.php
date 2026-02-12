<?php
namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample object which uses AlternateCodecable to implement
 * serialization/deserialization.
 */
class AlternateObject implements AlternateCodecable {
	/** @var mixed */
	public $property;

	/**
	 * Create a new AlternateObject which stores $property.
	 * @param mixed $property
	 */
	public function __construct( $property ) {
		$this->property = $property;
	}

	// Implement AlternateCodecable

	/** @inheritDoc */
	public function jsonSerialize(): array {
		return [ 'my custom field' => $this->property ];
	}

	/** @inheritDoc */
	public static function jsonUnserialize( array $json ) {
		return new AlternateObject( $json['my custom field'] );
	}
}
