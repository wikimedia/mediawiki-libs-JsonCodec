<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

/**
 * An object factory services which can be used to assist with
 * serialization/deserialization of objects.
 */
class ServicesObjectFactory {
	/** @var array<string,ServicesObject> Fake database */
	private $storage = [];

	/**
	 * Create and store an object with $name and $value in the database.
	 * @param string $name
	 * @param int $value
	 * @return ServicesObject
	 */
	public function create( string $name, int $value ): ServicesObject {
		if ( isset( $this->storage[$name] ) ) {
			throw new \Error( "duplicate name" );
		}
		$this->storage[$name] = $o = new ServicesObject( $name, $value );
		return $o;
	}

	/**
	 * Lookup $name in the database.
	 * @param string $name
	 * @return ServicesObject
	 */
	public function lookup( string $name ): ServicesObject {
		if ( !isset( $this->storage[$name] ) ) {
			throw new \Error( "not found" );
		}
		return $this->storage[$name];
	}
}
