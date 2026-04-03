<?php
declare( strict_types = 1 );

namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample BackedEnum using int backing values.
 */
enum IntEnum : int {
	case ONE = 1;
	case TWO = 2;
	case THREE = 3;
}
