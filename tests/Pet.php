<?php
namespace Wikimedia\JsonCodec\Tests;

use JsonException;
use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;

/**
 * Sample object which uses Hint::INHERITED to handle subclass instantiation.
 */
abstract class Pet implements JsonCodecable {
	use JsonCodecableTrait;

	public string $name;

	/**
	 * Create a new Pet with the given $name
	 * @param string $name
	 */
	public function __construct( string $name ) {
		$this->name = $name;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return [ 'name' => $this->name ];
	}

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ) {
		// Note that we need to provide key name hints without (yet)
		// knowing the subclass type.
		return Dog::jsonClassHintFor( $keyName ) ??
			Cat::jsonClassHintFor( $keyName );
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): Pet {
		$name = strtolower( $json['name'] );
		switch ( $name ) {
			case 'fido':
			case 'rover':
				return Dog::newFromJsonArray( $json );
			case 'meow':
			case 'socks':
				return Cat::newFromJsonArray( $json );
			default:
				throw new JsonException( 'unknown pet' );
		}
	}
}
