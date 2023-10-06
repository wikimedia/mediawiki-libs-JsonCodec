<?php
// phpcs:disable Generic.Files.LineLength.TooLong
namespace Wikimedia\JsonCodec\Tests;

/**
 * @coversDefaultClass \Wikimedia\JsonCodec\JsonCodec
 */
class ReservedKeyCodecTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test the ::isArrayMarked()/::markArray()/::unmarkArray() mechanism
	 * @covers ::toJsonString
	 * @covers ::newFromJsonString
	 * @dataProvider \Wikimedia\JsonCodec\Tests\JsonCodecTest::provideBasicValues
	 */
	public function testReservedKeyCodec( $value, $strict = true ) {
		$c = new ReservedKeyCodec();
		$s = $c->toJsonString( $value );
		$v = $c->newFromJsonString( $s );
		if ( $strict ) {
			$this->assertSame( $value, $v );
		} else {
			$this->assertEquals( $value, $v );
		}
	}
}
