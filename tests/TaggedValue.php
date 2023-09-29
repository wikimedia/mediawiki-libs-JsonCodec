<?php
namespace Wikimedia\JsonCodec\Tests;

use Psr\Container\ContainerInterface;
use Wikimedia\JsonCodec\JsonClassCodec;
use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecInterface;

/**
 * Sample object using manual control of implicit types, in both a tagged
 * value and a nested array.
 */
class TaggedValue implements JsonCodecable {
	/** @var string A tag implicitly giving the type of $taggedValue */
	public string $tag;
	/** @var mixed A tagged union value */
	public $taggedValue;
	/** @var SampleObject an object value which will be nested in an array */
	public SampleObject $nestedValue;

	/**
	 * Create a new TaggedValue.
	 * @param string $tag A tag describing the type of $taggedValue
	 * @param mixed $taggedValue
	 * @param SampleObject $nestedValue
	 */
	public function __construct(
		string $tag, $taggedValue, SampleObject $nestedValue
	) {
		$this->tag = $tag;
		$this->taggedValue = $taggedValue;
		$this->nestedValue = $nestedValue;
	}

	/**
	 * @param string $tag
	 * @return ?class-string<JsonCodecable>
	 */
	public static function tagToType( string $tag ): ?string {
		switch ( $tag ) {
		case 's':
			return SampleObject::class;
		case 'c':
			return SampleContainerObject::class;
		case 'm':
			return ManagedObject::class;
		default:
			// Any other tag will explicitly encode the type of the value
			// in the JSON.
			return null;
		}
	}

	/** @inheritDoc */
	public static function jsonClassCodec(
		JsonCodecInterface $codec,
		ContainerInterface $serviceContainer
	): JsonClassCodec {
		return new class( $codec ) implements JsonClassCodec {
			/** @var JsonCodecInterface */
			private JsonCodecInterface $codec;

			/** @param JsonCodecInterface $codec */
			public function __construct( JsonCodecInterface $codec ) {
				$this->codec = $codec;
			}

			/** @inheritDoc */
			public function toJsonArray( $obj ): array {
				'@phan-var TaggedValue $obj';
				return [
					// Using the JsonCodecInterface to provide an appropriate
					// implicit type hint for a tagged union value
					'tag' => $obj->tag,
					'value' => $this->codec->toJsonArray(
						$obj->taggedValue, TaggedValue::tagToType( $obj->tag )
					),
					// Using the JsonCodecInterface to provide an appropriate
					// implicit type hint for a value nested in an array
					'nested' => [ 'value' => $this->codec->toJsonArray(
						$obj->nestedValue, SampleObject::class
					) ],
				];
			}

			/** @inheritDoc */
			public function newFromJsonArray( string $className, array $json ) {
				// Deserializing a tagged union value
				$tag = $json['tag'];
				$taggedValue = $this->codec->newFromJsonArray(
					$json['value'], TaggedValue::tagToType( $tag )
				);
				// Deserializing an implicitly-typed value nested in an array
				$nestedValue = $this->codec->newFromJsonArray(
					$json['nested']['value'], SampleObject::class
				);
				// @phan-suppress-next-line PhanTypeMismatchReturn limitations of phan generics
				return new TaggedValue( $tag, $taggedValue, $nestedValue );
			}

			/** @inheritDoc */
			public function jsonClassHintFor( string $className, string $keyName ): ?string {
				// Our 'class hint for' mechanism is insufficient for
				// this use case since (a) the type of the 'value' key
				// is not fixed, and (b) the type of the nested value
				// is not a direct top-level key.  (Although We could
				// in theory work around (b) by inventing a new
				// pseudo-type for the 'nested' key, which would have a
				// matching class codec which gave a hint for the
				// 'value' key of the pseudo-type.)
				return null;
			}
		};
	}
}
