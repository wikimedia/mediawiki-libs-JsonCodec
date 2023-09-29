<?php
// phpcs:disable Generic.Files.LineLength.TooLong
namespace Wikimedia\JsonCodec\Tests;

/**
 * @coversDefaultClass \Wikimedia\JsonCodec\JsonCodec
 */
class AlternateCodecTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test the ::addCodecFor() mechanism on a subclass of JsonCodec.
	 * @covers ::addCodecFor()
	 * @dataProvider \Wikimedia\JsonCodec\Tests\JsonCodecTest::provideBasicValues
	 * @dataProvider provideAlternateValues
	 */
	public function testAlternateCodec( $value, $strict = true ) {
		$c = new AlternateCodec();
		$s = $c->toJsonString( $value );
		$v = $c->newFromJsonString( $s );
		if ( $strict ) {
			$this->assertSame( $value, $v );
		} else {
			$this->assertEquals( $value, $v );
		}
	}

	public function provideAlternateValues() {
		return [
			[ new AlternateObject( 'a' ), false ],
		];
	}
}
