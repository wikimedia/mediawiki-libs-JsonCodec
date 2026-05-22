<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\CodecService;
use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableWithServicesTrait;

/**
 * A variation of a managed object which uses JsonServicesClassCodec
 * and JsonCodecableWithServicesTrait.
 */
class ServicesObject implements JsonCodecable {
	use JsonCodecableWithServicesTrait;

	/** @var string */
	public string $name;
	/** @var int */
	public int $data;

	/**
	 * Create a new ServicesObject which stores $property.  This constructor
	 * shouldn't be invoked directly by anyone except ServicesObjectFactory.
	 *
	 * @param string $name
	 * @param int $data
	 * @internal
	 */
	public function __construct( string $name, int $data ) {
		$this->name = $name;
		$this->data = $data;
	}

	// Implement JsonCodecable by using the ServicesObjectFactory service.

	// No extra services required for serialization in this case.
	public function toJsonArray(
		// Test support of optional services, using both spellings of
		// "this type allows null"
		#[CodecService( 'OptionalService1', optional: true )]
			?ServicesObjectFactory $optionalService1,
		#[CodecService( 'OptionalService2', optional: true )]
			ServicesObjectFactory|null $optionalService2,
	): array {
		// Not necessary to serialize all the properties, since they
		// will be reloaded from the "database" during deserialization
		return [ 'name' => $this->name ];
	}

	// The ServicesObjectFactory is used for deserialization
	public static function newFromJsonArray(
		array $json,
		#[CodecService( 'ServicesObjectFactory' )] ServicesObjectFactory $sof,
	): static {
		return $sof->lookup( $json['name'] );
	}
}
