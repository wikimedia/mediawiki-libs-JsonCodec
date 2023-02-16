<?php
namespace Wikimedia\JsonCodec\Tests;

use Psr\Container\ContainerInterface;
use Wikimedia\JsonCodec\JsonClassCodec;
use Wikimedia\JsonCodec\JsonCodecable;

/**
 * Managed object which uses a factory in a service.
 */
class ManagedObject implements JsonCodecable {

	/** @var string */
	public string $name;
	/** @var int */
	public int $data;

	/**
	 * Create a new ManagedObject which stores $property.
	 * @param string $name
	 * @param int $data
	 */
	public function __construct( string $name, int $data ) {
		$this->name = $name;
		$this->data = $data;
	}

	// Implement JsonCodecable

	/** @inheritDoc */
	public static function jsonClassCodec( ContainerInterface $serviceContainer ): JsonClassCodec {
		return $serviceContainer->get( 'ManagedObjectFactory' );
	}
}
