<?php

namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonCodec;

/**
 * @coversDefaultClass \Wikimedia\JsonCodec\JsonCodec
 */
class JsonCodecTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::toJsonString
	 * @covers ::newFromJsonString
	 * @dataProvider provideBasicValues
	 * @dataProvider provideCodecableValues
	 */
	public function testBasicFunctionality( $value, $strict = true ) {
		$c = new JsonCodec();
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
}
