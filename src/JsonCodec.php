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
	 * @param mixed|null $value
	 * @return string
	 */
	public function toJsonString( $value ): string {
		return json_encode(
			$this->toJsonArray( $value ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
			JSON_HEX_TAG | JSON_HEX_AMP
		);
	}

	/**
	 * Recursively converts a JSON-encoded string to an object value or scalar.
	 * While deserializing the $json JsonCodec delegates to the appropriate
	 * JsonClassCodecs of any classes which implement JsonCodecable.
	 *
	 * @param string $json A JSON-encoded string
	 * @return mixed|null
	 */
	public function newFromJsonString( $json ) {
		return $this->newFromJsonArray(
			json_decode( $json, true )
		);
	}

	/**
	 * Maintain a cache giving the codec for a given class name.
	 *
	 * Reusing this JsonCodec object will also reuse this cache, which
	 * could improve performance somewhat.
	 *
	 * @param class-string<JsonCodecable> $className
	 * @return JsonClassCodec
	 */
	protected function codecFor( string $className ): JsonClassCodec {
		$codec = $this->codecs[$className] ?? null;
		if ( !$codec ) {
			$codec = $this->codecs[$className] =
				   $className::jsonClassCodec( $this->serviceContainer );
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
	 * @param mixed|null $value
	 * @return mixed|null
	 */
	public function toJsonArray( $value ) {
		$is_complex = false;
		$className = 'array';
		if ( $value instanceof JsonCodecable ) {
			$className = get_class( $value );
			$value = $this->codecFor( $className )->toJsonArray( $value );
			$is_complex = true;
		} elseif (
			is_object( $value ) &&
			get_class( $value ) === stdClass::class
		) {
			$value = (array)$value;
			$className = stdClass::class;
			$is_complex = true;
		} elseif (
			is_array( $value ) &&
			array_key_exists( self::TYPE_ANNOTATION, $value )
		) {
			$is_complex = true;
		}
		if ( is_array( $value ) ) {
			// Recursively convert array values to serializable form
			foreach ( $value as $unused => &$v ) {
				if ( is_object( $v ) || is_array( $v ) ) {
					$v = $this->toJsonArray( $v );
					if ( array_key_exists( self::TYPE_ANNOTATION, $v ) ) {
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
					$value[self::TYPE_ANNOTATION] = [ $className, $value[self::TYPE_ANNOTATION] ];
				} else {
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
	 * @param mixed|null $json
	 * @return mixed|null
	 */
	public function newFromJsonArray( $json ) {
		if ( $json instanceof stdClass ) {
			// We *shouldn't* be given an object... but we might.
			$json = (array)$json;
		}
		// Is this an array containing a complex value?
		if ( is_array( $json ) && array_key_exists( self::TYPE_ANNOTATION, $json ) ) {
			// Read out our metadata
			$className = $json[self::TYPE_ANNOTATION];
			// Remove our marker and restore the previous state of the
			// json array (restoring a pre-existing field if needed)
			if ( is_array( $className ) ) {
				$json[self::TYPE_ANNOTATION] = $className[1];
				$className = $className[0];
			} else {
				unset( $json[self::TYPE_ANNOTATION] );
			}
			// Recursively unserialize the array contents.
			$unserialized = [];
			foreach ( $json as $key => $value ) {
				if ( is_array( $value ) && array_key_exists( self::TYPE_ANNOTATION, $value ) ) {
					$unserialized[$key] = $this->newFromJsonArray( $value );
				} else {
					$unserialized[$key] = $value;
				}
			}
			// Use a JsonCodec to create the object instance if appropriate.
			if ( $className === stdClass::class ) {
				$json = (object)$unserialized;
			} elseif ( $className !== 'array' ) {
				$json = $this->codecFor( $className )->newFromJsonArray( $className, $unserialized );
			} else {
				$json = $unserialized;
			}
		}
		return $json;
	}
}
