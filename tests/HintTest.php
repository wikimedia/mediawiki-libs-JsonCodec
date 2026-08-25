<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\Hint;
use Wikimedia\JsonCodec\HintType;

/**
 * @coversDefaultClass \Wikimedia\JsonCodec\Hint
 */
class HintTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::build
	 */
	public function testBuildNoModifiersReturnsBareClass() {
		$this->assertSame( self::class, Hint::build( self::class ) );
	}

	/**
	 * @covers ::build
	 */
	public function testBuildNestsRightToLeft() {
		// stdclass<Foo[]> as a list of lists.
		$h = Hint::build( self::class, HintType::LIST, HintType::STDCLASS );
		$this->assertInstanceOf( Hint::class, $h );
		$this->assertSame( HintType::STDCLASS, $h->modifier );
		$this->assertInstanceOf( Hint::class, $h->parent );
		$this->assertSame( HintType::LIST, $h->parent->modifier );
		$this->assertSame( self::class, $h->parent->parent );
	}

	/**
	 * @covers ::hasModifier
	 */
	public function testHasModifierFalseForBareString() {
		$this->assertFalse( Hint::hasModifier( self::class, HintType::INHERITED ) );
	}

	/**
	 * @covers ::hasModifier
	 */
	public function testHasModifierFindsOutermostMatch() {
		$h = Hint::build( self::class, HintType::INHERITED );
		$this->assertTrue( Hint::hasModifier( $h, HintType::INHERITED ) );
		$this->assertFalse( Hint::hasModifier( $h, HintType::LIST ) );
	}

	/**
	 * @covers ::hasModifier
	 */
	public function testHasModifierDoesNotLookInsideListOrStdclass() {
		// The INHERITED modifier is on the item type, inside a LIST;
		// hasModifier() only inspects the outermost (LIST) hint.
		$h = Hint::build( self::class, HintType::INHERITED, HintType::LIST );
		$this->assertFalse( Hint::hasModifier( $h, HintType::INHERITED ) );
		$this->assertTrue( Hint::hasModifier( $h, HintType::LIST ) );
	}

	/**
	 * @covers ::hasModifier
	 */
	public function testHasModifierLooksThroughNonContainerModifiers() {
		// USE_SQUARE doesn't stop the search the way LIST/STDCLASS do.
		$h = Hint::build( self::class, HintType::INHERITED, HintType::USE_SQUARE );
		$this->assertTrue( Hint::hasModifier( $h, HintType::INHERITED ) );
		$this->assertTrue( Hint::hasModifier( $h, HintType::USE_SQUARE ) );
	}

	/**
	 * @covers ::isSame
	 */
	public function testIsSameStrings() {
		$this->assertTrue( Hint::isSame( self::class, self::class ) );
		$this->assertFalse( Hint::isSame( self::class, Hint::class ) );
	}

	/**
	 * @covers ::isSame
	 */
	public function testIsSameStringVsHintAlwaysFalse() {
		$h = Hint::build( self::class, HintType::LIST );
		$this->assertFalse( Hint::isSame( self::class, $h ) );
		$this->assertFalse( Hint::isSame( $h, self::class ) );
	}

	/**
	 * @covers ::isSame
	 */
	public function testIsSameHintsCompareStructureNotIdentity() {
		$a = Hint::build( self::class, HintType::LIST, HintType::STDCLASS );
		$b = Hint::build( self::class, HintType::LIST, HintType::STDCLASS );
		$this->assertNotSame( $a, $b );
		$this->assertTrue( Hint::isSame( $a, $b ) );
	}

	/**
	 * @covers ::isSame
	 */
	public function testIsSameDetectsModifierOrClassDifference() {
		$a = Hint::build( self::class, HintType::LIST, HintType::STDCLASS );
		$diffModifier = Hint::build( self::class, HintType::STDCLASS, HintType::STDCLASS );
		$diffClass = Hint::build( Hint::class, HintType::LIST, HintType::STDCLASS );
		$this->assertFalse( Hint::isSame( $a, $diffModifier ) );
		$this->assertFalse( Hint::isSame( $a, $diffClass ) );
	}
}
