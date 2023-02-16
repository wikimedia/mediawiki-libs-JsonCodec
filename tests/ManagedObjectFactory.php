<?php
namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonClassCodec;

/**
 * Managed object which uses a factory in a service.
 *
 * @implements JsonClassCodec<ManagedObject>
 */
class ManagedObjectFactory implements JsonClassCodec {
	/** @var array<string,ManagedObject> */
	private $storage = [];

	/**
	 * Create and store an object with $name and $value in the database.
	 * @param string $name
	 * @param int $value
	 * @return ManagedObject
	 */
	public function create( string $name, int $value ) {
		if ( isset( $this->storage[$name] ) ) {
			throw new \Error( "duplicate name" );
		}
		$this->storage[$name] = $o = new ManagedObject( $name, $value );
		return $o;
	}

	/**
	 * Lookup $name in the database.
	 * @param string $name
	 * @return ManagedObject
	 */
	public function lookup( string $name ): ManagedObject {
		if ( !isset( $this->storage[$name] ) ) {
			throw new \Error( "not found" );
		}
		return $this->storage[$name];
	}

	/** @inheritDoc */
	public function toJsonArray( $obj ): array {
		'@phan-var ManagedObject $obj';
		// Not necessary to serialize all the properties
		return [ 'name' => $obj->name ];
	}

	/** @inheritDoc */
	public function newFromJsonArray( string $className, array $json ): ManagedObject {
		// @phan-suppress-next-line PhanTypeMismatchReturn template limitations
		return $this->lookup( $json['name'] );
	}
}
