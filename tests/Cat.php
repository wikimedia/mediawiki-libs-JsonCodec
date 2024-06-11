<?php
namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample object which uses Hint::INHERITED to handle subclass instantiation.
 */
class Cat extends Pet {

	public ?Dog $enemy;

	/**
	 * Create a new Cat with the given name and enemy
	 * @param string $name
	 * @param ?Dog $enemy
	 */
	public function __construct( string $name, ?Dog $enemy ) {
		parent::__construct( $name );
		$this->enemy = $enemy;
	}

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return parent::toJsonArray() + (
			$this->enemy === null ? [] : [ 'enemy' => $this->enemy ]
		);
	}

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ) {
		if ( $keyName === 'enemy' ) {
			return Dog::class;
		}
		return null;
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): Cat {
		return new Cat( $json['name'], $json['enemy'] );
	}
}
