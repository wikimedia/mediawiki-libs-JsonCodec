<?php

namespace Wikimedia\JsonCodec\Tests;

use Psr\Container\ContainerInterface;
use Wikimedia\JsonCodec\JsonCodec;

/**
 * @coversDefaultClass \Wikimedia\JsonCodec\JsonCodec
 */
class JsonCodecTest extends \PHPUnit\Framework\TestCase {

	private static function getServices(): ContainerInterface {
		static $services = null;
		if ( !$services ) {
			$services = new class implements ContainerInterface {
				private $storage = [];

				public function get( $id ) {
					return $this->storage[$id];
				}

				public function has( $id ) {
					return isset( $this->storage[$id] );
				}

				public function set( $id, $value ) {
					$this->storage[$id] = $value;
				}
			};
			$factory = new ManagedObjectFactory();
			$factory->create( "a", 1 );
			$factory->create( "b", 2 );
			$services->set( 'ManagedObjectFactory', $factory );
		}
		return $services;
	}

	/**
	 * @covers ::toJsonString
	 * @covers ::newFromJsonString
	 * @dataProvider provideBasicValues
	 * @dataProvider provideCodecableValues
	 * @dataProvider provideManagedValues
	 */
	public function testBasicFunctionality( $value, $strict = true ) {
		$c = new JsonCodec( self::getServices() );
		$s = $c->toJsonString( $value );
		$v = $c->newFromJsonString( $s );
		if ( $strict ) {
			$this->assertSame( $value, $v );
		} else {
			$this->assertEquals( $value, $v );
		}
	}

	public function provideBasicValues() {
		return [
			[ null ],
			[ '' ],
			[ [] ],
			[ 42 ],
			[ [ 'a' => 1, 'b' => 2 ] ],
		];
	}

	public function provideCodecableValues() {
		return [
			[ new SampleObject( 'a' ), false ],
			[ new SampleObject( 'abc123' ), false ],
		];
	}

	public function provideManagedValues() {
		$factory = self::getServices()->get( 'ManagedObjectFactory' );
		return [
			[ $factory->lookup( 'a' ) ],
			[ $factory->lookup( 'b' ) ],
		];
	}
}
