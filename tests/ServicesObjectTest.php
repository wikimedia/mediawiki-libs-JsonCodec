<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonCodecableWithServicesTestTrait;

/**
 * Demonstrates JsonCodecableWithServicesTestTrait as a unit test: verifies
 * only that ServicesObject's ::toJsonArray()/::newFromJsonArray() methods
 * are declared with the signature (and #[CodecService] attributes)
 * required by JsonCodecableWithServicesTrait.
 */
class ServicesObjectTest extends \PHPUnit\Framework\TestCase {
	use JsonCodecableWithServicesTestTrait;

	protected static function getCodecableClass(): string {
		return ServicesObject::class;
	}
}
