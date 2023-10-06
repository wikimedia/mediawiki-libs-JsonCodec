<?php
declare( strict_types=1 );

namespace Wikimedia\JsonCodec\Tests;

use stdClass;
use Wikimedia\JsonCodec\JsonCodec;

/**
 * An example of extending JsonCodec to remap/abbreviate type names,
 * change the key used to encode types, and reserve certain key names.
 */
class ReservedKeyCodec extends JsonCodec {
	protected array $classMap = [];
	protected array $reverseClassMap = [];

	public function __construct() {
		parent::__construct();
		$this->addSchemaClass( 'array' );
		$this->addSchemaClass( stdClass::class );
		$this->addSchemaClass( SampleObject::class );
		$this->addSchemaClass( SampleContainerObject::class );
	}

	/**
	 * Add a new class to the schema; this class will be encoded with a short
	 * numeric value.
	 * @param string $className
	 */
	public function addSchemaClass( string $className ): void {
		// $idx zero is reserved for 'matches class hint'
		$idx = count( $this->classMap ) + 1;
		$this->classMap[$className] = $idx;
		$this->reverseClassMap[$idx] = $className;
	}

	/** @inheritDoc */
	protected function isArrayMarked( array $value ): bool {
		// Use our own mark!
		return array_key_exists( '@', $value );
	}

	protected function markArray( array &$arr, string $className, ?string $classHint ): void {
		// We're going to record marks using '@' and rename any existing
		// key starting with '@' to include one additional '@'
		$remapped = [];
		foreach ( $arr as $key => $value ) {
			if ( str_starts_with( $key, '@' ) ) {
					$remapped['@' . $key] = $value;
					unset( $arr[$key] );
			}
		}
		foreach ( $remapped as $key => $value ) {
			$arr[$key] = $value;
		}
		// Use our own class name mapping for compactness!
		if ( array_key_exists( $className, $this->classMap ) ) {
			$arr['@'] = $this->classMap[$className];
		} elseif ( $className === $classHint ) {
			$arr['@'] = 0;
		} else {
			$arr['@'] = $className;
		}
	}

	protected function unmarkArray( array &$arr, ?string $classHint ): string {
		$className = $classHint;
		if ( array_key_exists( '@', $arr ) ) {
			$className = $arr['@'];
			if ( array_key_exists( $className, $this->reverseClassMap ) ) {
				$className = $this->reverseClassMap[$className];
			}
			unset( $arr['@'] );
		}
		// Undo the mapping which added extra '@' to key names
		$remapped = [];
		foreach ( $arr as $key => $value ) {
			if ( str_starts_with( $key, '@' ) ) {
				$remapped[substr( $key, 1 )] = $value;
				unset( $arr[$key] );
			}
		}
		foreach ( $remapped as $key => $value ) {
			$arr[$key] = $value;
		}
		return $className;
	}
}
