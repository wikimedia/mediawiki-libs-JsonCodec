<?php
// phpcs:disable Generic.Files.LineLength.TooLong
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
			[ (object)[ 'a' => 1, 'b' => 2 ], false ],
			// even arrays which contain the "protected" field names should
			// be fine.
			[ [ '_type_' => 'lalala' ] ],
		];
	}

	public function provideCodecableValues() {
		return [
			[ new SampleObject( 'a' ), false ],
			[ new SampleObject( 'abc123' ), false ],
			[ new SampleObject( 'suppress _type_' ), false ],
			[ new SampleContainerObject( (object)[ 'a' => 1 ] ), false ],
			[ new SampleContainerObject( new SampleObject( 'partially hinted' ) ), false ],
			[ new SampleContainerObject( new SampleObject( 'suppress _type_' ) ), false ],
		];
	}

	public function provideManagedValues() {
		$factory = self::getServices()->get( 'ManagedObjectFactory' );
		return [
			[ $factory->lookup( 'a' ) ],
			[ $factory->lookup( 'b' ) ],
		];
	}

	/**
	 * @covers ::toJsonString
	 * @covers ::newFromJsonString
	 * @dataProvider provideHintedValues
	 */
	public function testClassHints( $value, $classHint, $encoding = null, $strict = false ) {
		$c = new JsonCodec( self::getServices() );
		$s = $c->toJsonString( $value, $classHint );
		if ( $encoding !== null ) {
			$this->assertEquals( $encoding, $s );
		}
		$v = $c->newFromJsonString( $s, $classHint );
		if ( $strict ) {
			$this->assertSame( $value, $v );
		} else {
			$this->assertEquals( $value, $v );
		}
	}

	public function provideHintedValues() {
		$factory = self::getServices()->get( 'ManagedObjectFactory' );
		return [
			'sample object no hint' => [
				new SampleObject( 'no hint' ), null,
				'{"property":"no hint","_type_":["check123","Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleObject"]}'
			],
			'sample object correct hint no _type_' => [
				new SampleObject( 'suppress _type_' ), SampleObject::class,
				'{"property":"suppress _type_"}'
			],
			'sample object correct hint with _type_' => [
				new SampleObject( 'right hint' ), SampleObject::class,
				'{"property":"right hint","_type_":["check123"]}'
			],
			'sample object wrong hint' => [
				new SampleObject( 'wrong hint' ), SampleContainerObject::class,
				'{"property":"wrong hint","_type_":["check123","Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleObject"]}'
			],

			'string container object no hint' => [
				new SampleContainerObject( 'string contents' ), null,
				'{"contents":"string contents","test":[],"array":["string contents"],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'string container object wrong hint' => [
				new SampleContainerObject( 'string contents' ), SampleObject::class,
				'{"contents":"string contents","test":[],"array":["string contents"],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'string container object right hint' => [
				new SampleContainerObject( 'string contents' ), SampleContainerObject::class,
				'{"contents":"string contents","test":[],"array":["string contents"]}'
			],

			'stdClass container object no hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), null,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":[],"array":[{"a":1,"_type_":"stdClass"}],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'stdClass container object wrong hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), SampleObject::class,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":[],"array":[{"a":1,"_type_":"stdClass"}],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'stdClass container object right hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), SampleContainerObject::class,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":[],"array":[{"a":1,"_type_":"stdClass"}]}'
			],

			'managed object container right top level hint' =>
			[
				new SampleContainerObject( $factory->lookup( 'a' ) ), SampleContainerObject::class,
				'{"contents":{"name":"a","_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\ManagedObject"},"test":[],"array":[{"name":"a","_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\ManagedObject"}]}'
			],

			// Very succinct output when all type hints match up
			'sample object container object correct hints' => [
				new SampleContainerObject( new SampleObject( 'suppress _type_' ) ), SampleContainerObject::class,
				'{"contents":{"property":"suppress _type_"},"test":[],"array":[{"property":"suppress _type_"}]}'
			],

			'tagged value correct hints' => [
				new TaggedValue( 'm', $factory->lookup( 'a' ), new SampleObject( 'suppress _type_' ) ), TaggedValue::class,
				'{"tag":"m","value":{"name":"a"},"nested":{"value":{"property":"suppress _type_"},"some other value":{"x":"y"}}}'
			],
		];
	}

}
