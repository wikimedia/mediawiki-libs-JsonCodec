<?php
declare( strict_types=1 );
//phpcs:disable MediaWiki.Commenting.FunctionComment.ObjectTypeHintReturn
namespace Wikimedia\JsonCodec\Tests;

/**
 * See AlternateCodec for an example of supporting this interface as
 * an alternative serialization interface.
 */
interface AlternateCodecable extends \JsonSerializable {
	// This interface inherits jsonSerialize() from \JsonSerializable

	/**
	 * Create a new object from the output of ::jsonSerialize().
	 * @param array $value
	 * @return object
	 */
	public static function jsonUnserialize( array $value );
}
