<?php
namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;

/**
 * Sample UnitEnum using no backing values and a custom serializer.
 */
enum CustomEnum implements JsonCodecable {
	use JsonCodecableTrait;

	case ONE;
	case TWO;
	case THREE;

	/** @inheritDoc */
	public function toJsonArray(): array {
		return match ( $this ) {
			self::ONE => [ 'one' ],
			self::TWO => [ 2 ],
			self::THREE => [ 'III' ],
		};
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): self {
		return match ( $json[0] ) {
			'one' => self::ONE,
			2 => self::TWO,
			'III' => self::THREE,
		};
	}
}
