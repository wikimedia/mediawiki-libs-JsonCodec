<?php
declare( strict_types=1 );

namespace Wikimedia\JsonCodec\Tests;

use Wikimedia\JsonCodec\JsonClassCodec;
use Wikimedia\JsonCodec\JsonCodec;

/**
 * An example of extending JsonCodec to support other
 * serialization/deserialization interfaces.
 */
class AlternateCodec extends JsonCodec {
	/** @inheritDoc */
	protected function codecFor( string $className ): ?JsonClassCodec {
		$codec = parent::codecFor( $className );
		if ( $codec === null && is_a( $className, AlternateCodecable::class, true ) ) {
			/** @implements JsonClassCodec<AlternateCodecable> */
			$codec = new class() implements JsonClassCodec {
				/** @inheritDoc */
				public function toJsonArray( $obj ): array {
					return $obj->jsonSerialize();
				}

				/** @inheritDoc */
				public function newFromJsonArray( string $className, array $json ) {
					return $className::jsonUnserialize( $json );
				}

				/** @inheritDoc */
				public function jsonClassHintFor( string $className, string $keyName ) {
					return null;
				}
			};
			// Cache this for future use
			$this->addCodecFor( $className, $codec );
		}
		return $codec;
	}
}
