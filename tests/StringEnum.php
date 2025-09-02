<?php
namespace Wikimedia\JsonCodec\Tests;

/**
 * Sample BackedEnum using string backing values.
 */
enum StringEnum : string {
	case ONE = 'one';
	case TWO = 'two';
	case THREE = 'three';
}
