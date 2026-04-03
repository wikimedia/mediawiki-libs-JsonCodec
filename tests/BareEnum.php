<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample UnitEnum using no backing values.
 */
enum BareEnum {
	case ONE;
	case TWO;
	case THREE;
}
