<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Json
 */

namespace Wikimedia\JsonCodec;

use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

/**
 * Helper class to serialize/unserialize things to/from JSON.
 */
class JsonCodec {
	/** @var ContainerInterface Service container */
	protected ContainerInterface $serviceContainer;

	/** @var array<class-string,JsonClassCodec> Class codecs */
	protected array $codecs;

	/**
	 * Name of the property where class information is stored; it also
	 * is used to mark "complex" arrays, and as a place to store the contents
	 * of any pre-existing array property that happened to have the same name.
	 */
	private const TYPE_ANNOTATION = '_type_';

	/**
	 * @param ?ContainerInterface $serviceContainer
	 */
	public function __construct( ?ContainerInterface $serviceContainer = null ) {
		$this->serviceContainer = $serviceContainer ??
			// Use an empty container if none is provided.
			new class implements ContainerInterface {
				/**
				 * @param string $id
				 * @return never
				 */
				public function get( $id ) {
					throw new class( "not found" ) extends Exception implements NotFoundExceptionInterface {
					};
				}

				/** @inheritDoc */
				public function has( string $id ): bool {
					return false;
				}
			};
	}

	/**
	 * Recursively converts a given object to a JSON-encoded string.
	 * While serializing the $value JsonCodec delegates to the appropriate
	 * JsonClassCodecs of any classes which implement JsonCodecable.
	 *
	 * If a $classHint is provided and matches the type of the value,
	 * then type information will not be included in the generated JSON;
	 * otherwise an appropriate class name will be added to the JSON to
	 * guide deserialization.
	 *
	 * @param mixed|null $value
	 * @param ?class-string<JsonCodecable> $classHint An optional hint to
	 *   the type of the encoded object.  If this is provided and matches
	 *   the type of $value, then explicit type information will be omitted
	 *   from the generated JSON, which saves some space.
	 * @return string
	 */
	public function toJsonString( $value, ?string $classHint = null ): string {
		return json_encode(
			$this->toJsonArray( $value, $classHint ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
			JSON_HEX_TAG | JSON_HEX_AMP
		);
	}

	/**
	 * Recursively converts a JSON-encoded string to an object value or scalar.
	 * While deserializing the $json JsonCodec delegates to the appropriate
	 * JsonClassCodecs of any classes which implement JsonCodecable.
	 *
	 * For objects encoded using implicit class information, a "class hint"
	 * can be provided to guide deserialization; this is unnecessary for
	 * objects serialized with explicit classes.
	 *
	 * @param string $json A JSON-encoded string
	 * @param ?class-string<JsonCodecable> $classHint An optional hint to
	 *   the type of the encoded object.  In the absence of explicit
	 *   type information in the JSON, this will be used as the type of
	 *   the created object.
	 * @return mixed|null
	 */
	public function newFromJsonString( $json, ?string $classHint = null ) {
		return $this->newFromJsonArray(
			json_decode( $json, true ), $classHint
		);
	}

	/**
	 * Maintain a cache giving the codec for a given class name.
	 *
	 * Reusing this JsonCodec object will also reuse this cache, which
	 * could improve performance somewhat.
	 *
	 * @param class-string<JsonCodecable>|class-string<stdClass> $className
	 * @return JsonClassCodec
	 */
	protected function codecFor( string $className ): JsonClassCodec {
		$codec = $this->codecs[$className] ?? null;
		if ( !$codec ) {
			$codec = $this->codecs[$className] = (
				$className === stdClass::class ?
				JsonStdClassCodec::getInstance() :
				$className::jsonClassCodec( $this->serviceContainer )
			);
		}
		return $codec;
	}

	/**
	 * Recursively converts a given object to an associative array
	 * which can be json-encoded.  (When embeddeding an object into
	 * another context it is sometimes useful to have the array
	 * representation rather than the string JSON form of the array;
	 * this can also be useful if you want to pretty-print the result,
	 * etc.)  While converting $value the JsonCodec delegates to the
	 * appropriate JsonClassCodecs of any classes which implement
	 * JsonCodecable.
	 *
	 * If a $classHint is provided and matches the type of the value,
	 * then type information will not be included in the generated JSON;
	 * otherwise an appropriate class name will be added to the JSON to
	 * guide deserialization.
	 *
	 * @param mixed|null $value
	 * @param ?class-string<JsonCodecable> $classHint An optional hint to
	 *   the type of the encoded object.  If this is provided and matches
	 *   the type of $value, then explicit type information will be omitted
	 *   from the generated JSON, which saves some space.
	 * @return mixed|null
	 */
	public function toJsonArray( $value, ?string $classHint = null ) {
		$is_complex = false;
		$className = 'array';
		$codec = null;
		if (
			$value instanceof JsonCodecable || (
				is_object( $value ) && get_class( $value ) === stdClass::class
			)
		) {
			$className = get_class( $value );
			$codec = $this->codecFor( $className );
			$value = $codec->toJsonArray( $value );
			$is_complex = true;
		} elseif (
			is_array( $value ) &&
			array_key_exists( self::TYPE_ANNOTATION, $value )
		) {
			$is_complex = true;
		}
		if ( is_array( $value ) ) {
			// Recursively convert array values to serializable form
			foreach ( $value as $key => &$v ) {
				if ( is_object( $v ) || is_array( $v ) ) {
					// phan can't tell that $codec is null when $className is 'array'
					$propClassHint = $codec === null ? null :
						// @phan-suppress-next-line PhanUndeclaredClassReference
						$codec->jsonClassHintFor( $className, $key );
					$v = $this->toJsonArray( $v, $propClassHint );
					if (
						array_key_exists( self::TYPE_ANNOTATION, $v ) ||
						$propClassHint !== null
					) {
						// an array which contains complex components is
						// itself complex.
						$is_complex = true;
					}
				}
			}
			// Ok, now mark the array, being careful to transfer away
			// any fields with the same names as our markers.
			if ( $is_complex ) {
				if ( array_key_exists( self::TYPE_ANNOTATION, $value ) ) {
					if ( $classHint !== $className ) {
						$value[self::TYPE_ANNOTATION] = [ $value[self::TYPE_ANNOTATION], $className ];
					} else {
						// Omit $className since it matches the $classHint
						$value[self::TYPE_ANNOTATION] = [ $value[self::TYPE_ANNOTATION] ];
					}
				} elseif ( $classHint !== $className ) {
					// Only include the type annotation if it doesn't match the hint
					$value[self::TYPE_ANNOTATION] = $className;
				}
			}
		} elseif ( !is_scalar( $value ) && $value !== null ) {
			throw new InvalidArgumentException(
				'Unable to serialize JSON.'
			);
		}
		return $value;
	}

	/**
	 * Recursively converts an associative array (or scalar) to an
	 * object value (or scalar).  While converting this value JsonCodec
	 * delegates to the appropriate JsonClassCodecs of any classes which
	 * implement JsonCodecable.
	 *
	 * For objects encoded using implicit class information, a "class hint"
	 * can be provided to guide deserialization; this is unnecessary for
	 * objects serialized with explicit classes.
	 *
	 * @param mixed|null $json
	 * @param ?class-string<JsonCodecable> $classHint An optional hint to
	 *   the type of the encoded object.  In the absence of explicit
	 *   type information in the JSON, this will be used as the type of
	 *   the created object.
	 * @return mixed|null
	 */
	public function newFromJsonArray( $json, ?string $classHint = null ) {
		if ( $json instanceof stdClass ) {
			// We *shouldn't* be given an object... but we might.
			$json = (array)$json;
		}
		// Is this an array containing a complex value?
		if (
			is_array( $json ) && (
				array_key_exists( self::TYPE_ANNOTATION, $json ) ||
				$classHint !== null
			)
		) {
			// Read out our metadata
			$className = $json[self::TYPE_ANNOTATION] ?? $classHint;
			// Remove our marker and restore the previous state of the
			// json array (restoring a pre-existing field if needed)
			if ( is_array( $className ) ) {
				$json[self::TYPE_ANNOTATION] = $className[0];
				$className = $className[1] ?? $classHint;
			} else {
				unset( $json[self::TYPE_ANNOTATION] );
			}
			// Create appropriate codec
			$codec = null;
			if ( $className !== 'array' ) {
				$codec = $this->codecFor( $className );
			}
			// Recursively unserialize the array contents.
			$unserialized = [];
			foreach ( $json as $key => $value ) {
				$propClassHint = $codec === null ? null :
					// phan can't tell that $codec is null when $className is 'array'
					// @phan-suppress-next-line PhanUndeclaredClassReference
					$codec->jsonClassHintFor( $className, $key );
				if (
					is_array( $value ) && (
						array_key_exists( self::TYPE_ANNOTATION, $value ) ||
						$propClassHint !== null
					)
				) {
					$unserialized[$key] = $this->newFromJsonArray( $value, $propClassHint );
				} else {
					$unserialized[$key] = $value;
				}
			}
			// Use a JsonCodec to create the object instance if appropriate.
			if ( $className === 'array' ) {
				$json = $unserialized;
			} else {
				$json = $codec->newFromJsonArray( $className, $unserialized );
			}
		}
		return $json;
	}
}
