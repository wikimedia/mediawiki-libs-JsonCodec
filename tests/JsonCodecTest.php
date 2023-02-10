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
	 */
	public function testBasicFunctionality( $value ) {
		$c = new JsonCodec();
		$s = $c->toJsonString( $value );
		$v = $c->newFromJsonString( $s );
		$this->assertSame( $value, $v );
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
}
