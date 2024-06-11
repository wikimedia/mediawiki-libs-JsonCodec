<?php
namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample object which uses Hint::INHERITED to handle subclass instantiation.
 */
class Dog extends Pet {

	public array $tricks;

	/**
	 * Create a new Dog with the given name and tricks
	 * @param string $name
	 * @param string ...$tricks
	 */
	public function __construct( string $name, string ...$tricks ) {
		parent::__construct( $name );
		$this->tricks = $tricks;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ) {
		return null;
	}

	/** @inheritDoc */
	public function toJsonArray(): array {
		return parent::toJsonArray() + [ 'tricks' => $this->tricks ];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): Dog {
		return new Dog( $json['name'], ...$json['tricks'] );
	}
}
