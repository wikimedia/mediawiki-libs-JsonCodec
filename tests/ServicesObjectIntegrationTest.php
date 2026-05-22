<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

use Psr\Container\ContainerInterface;
use Wikimedia\JsonCodec\JsonCodecableWithServicesTestTrait;

/**
 * Demonstrates JsonCodecableWithServicesTestTrait as an integration test:
 * additionally overrides ::getCodecableClassServices() so that the
 * services named by #[CodecService] attributes are resolved from a real
 * container and checked against the declared parameter types.
 */
class ServicesObjectIntegrationTest extends \PHPUnit\Framework\TestCase {
	use JsonCodecableWithServicesTestTrait;

	protected static function getCodecableClass(): string {
		return ServicesObject::class;
	}

	protected function getServiceContainer(): ?ContainerInterface {
		$services = new class implements ContainerInterface {
			private array $storage = [];

			public function get( $id ) {
				return $this->storage[$id];
			}

			public function has( $id ): bool {
				return isset( $this->storage[$id] );
			}

			public function set( $id, $value ) {
				$this->storage[$id] = $value;
			}
		};
		$services->set( 'ServicesObjectFactory', new ServicesObjectFactory() );
		return $services;
	}
}
