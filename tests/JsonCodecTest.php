<?php
// phpcs:disable Generic.Files.LineLength.TooLong
namespace Wikimedia\JsonCodec\Tests;

use Psr\Container\ContainerInterface;
use stdClass;
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

				public function has( $id ): bool {
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
			[ new SampleObjectAlias( 'alias' ), false ],
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
				'{"contents":"string contents","test":{"_type_":"stdClass"},"array":["string contents"],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'string container object wrong hint' => [
				new SampleContainerObject( 'string contents' ), SampleObject::class,
				'{"contents":"string contents","test":{"_type_":"stdClass"},"array":["string contents"],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'string container object right hint' => [
				new SampleContainerObject( 'string contents' ), SampleContainerObject::class,
				'{"contents":"string contents","test":{"_type_":"stdClass"},"array":["string contents"]}'
			],

			'stdClass container object no hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), null,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":{"_type_":"stdClass"},"array":[{"a":1,"_type_":"stdClass"}],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'stdClass container object wrong hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), SampleObject::class,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":{"_type_":"stdClass"},"array":[{"a":1,"_type_":"stdClass"}],"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleContainerObject"}'
			],
			'stdClass container object right hint' => [
				new SampleContainerObject( (object)[ 'a' => 1 ] ), SampleContainerObject::class,
				'{"contents":{"a":1,"_type_":"stdClass"},"test":{"_type_":"stdClass"},"array":[{"a":1,"_type_":"stdClass"}]}'
			],

			'managed object container right top level hint' =>
			[
				new SampleContainerObject( $factory->lookup( 'a' ) ), SampleContainerObject::class,
				'{"contents":{"name":"a","_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\ManagedObject"},"test":{"_type_":"stdClass"},"array":[{"name":"a","_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\ManagedObject"}]}'
			],

			// Very succinct output when all type hints match up
			'sample object container object correct hints' => [
				new SampleContainerObject( new SampleObject( 'suppress _type_' ) ), SampleContainerObject::class,
				'{"contents":{"property":"suppress _type_"},"test":{"_type_":"stdClass"},"array":[{"property":"suppress _type_"}]}'
			],

			'tagged value correct hints' => [
				new TaggedValue( 'm', $factory->lookup( 'a' ), new SampleObject( 'suppress _type_' ) ), TaggedValue::class,
				'{"tag":"m","value":{"name":"a"},"nested":{"value":{"property":"suppress _type_"},"some other value":{"x":"y"}}}'
			],

			// Use '{...}' syntax for JSON encoding, even if all keys happen
			// to be numeric
			'numeric keys for array (no hint)' => [
				[ 1, 2, 3 ], null,
				'[1,2,3]'
			],
			'numeric keys for array (wrong hint)' => [
				[ 1, 2, 3 ], stdClass::class,
				'{"0":1,"1":2,"2":3,"_type_":"array"}'
			],
			'numeric keys for stdClass (hinted)' => [
				(object)[ 1, 2, 3 ], stdClass::class,
				'{"0":1,"1":2,"2":3,"_type_":"stdClass"}'
			],

			// Aliased hint
			'sample object aliased hint' => [
				new SampleObject( 'xyz' ), SampleObjectAlias::class,
				'{"property":"xyz","_type_":["check123"]}'
			],

			// Hint suffixes + and -
			'3-item list object, no hint' => [
				new SampleList( 1, 2, 3 ), null,
				'{"0":1,"1":2,"2":3,"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleList"}'
			],
			'3-item list object, hinted' => [
				new SampleList( 1, 2, 3 ), SampleList::class,
				// Despite the hint, we need to include the _type_ field to
				// preserve the curly braces in the output.
				'{"0":1,"1":2,"2":3,"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleList"}'
			],
			'3-item list object, - suffix' => [
				new SampleList( 1, 2, 3 ), SampleList::class . '-',
				// With the - suffix, we get array-like output.
				'[1,2,3]'
			],
			'empty list object, no hint' => [
				new SampleList(), null,
				'{"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleList"}'
			],
			'empty list object, hinted' => [
				new SampleList(), SampleList::class,
				// Again, we need to add the _type_ field because otherwise
				// this will serialize as '[]'
				'{"_type_":"Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleList"}'
			],
			'list object, + suffix' => [
				// But if you want `{}` you can use the + suffix
				// Note that ::toJsonArray() may return a stdClass object,
				// not an array, in this case.  Caller beware.
				new SampleList(), SampleList::class . '+',
				'{}'
			],

		];
	}

	/**
	 * @covers ::toJsonString
	 * @covers ::newFromJsonString
	 * @dataProvider provideAliasedHints
	 */
	public function testAliasedHints( $jsonString, $classHint ) {
		$c = new JsonCodec( self::getServices() );
		$v = $c->newFromJsonString( $jsonString, $classHint );
		$expected = new SampleObject( 'no hint' );
		$this->assertEquals( $expected, $v );
	}

	public static function provideAliasedHints() {
		// Json with non-aliased _type_
		$jsonNoAlias = '{"property":"no hint","_type_":["check123","Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleObject"]}';

		yield "Non-aliased type, no hint" => [ $jsonNoAlias, null ];
		yield "Non-aliased type, true hint" => [ $jsonNoAlias, SampleObject::class ];
		yield "Non-aliased type, aliased hint" => [ $jsonNoAlias, SampleObjectAlias::class ];

		// Json with aliased _type_
		// Note that the embedded type name is the *alias* in this test
		$jsonAlias = '{"property":"no hint","_type_":["check123","Wikimedia\\\\JsonCodec\\\\Tests\\\\SampleObjectAlias"]}';
		yield "Aliased type, no hint" => [ $jsonAlias, null ];
		yield "Aliased type, true hint" => [ $jsonAlias, SampleObject::class ];
		yield "Aliased type, aliased hint" => [ $jsonAlias, SampleObjectAlias::class ];
	}
}
