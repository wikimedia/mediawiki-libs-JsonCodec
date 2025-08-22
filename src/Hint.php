<?php
declare( strict_types=1 );

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
 */

namespace Wikimedia\JsonCodec;

use Stringable;

/**
 * Class hints with modifiers.
 * @template T
 */
class Hint implements Stringable {

	/** @see HintType::DEFAULT */
	public const DEFAULT = HintType::DEFAULT;
	/** @see HintType::LIST */
	public const LIST = HintType::LIST;
	/** @see HintType::STDCLASS */
	public const STDCLASS = HintType::STDCLASS;
	/** @see HintType::USE_SQUARE */
	public const USE_SQUARE = HintType::USE_SQUARE;
	/** @see HintType::ALLOW_OBJECT */
	public const ALLOW_OBJECT = HintType::ALLOW_OBJECT;
	/** @see HintType::INHERITED */
	public const INHERITED = HintType::INHERITED;
	/** @see HintType::ONLY_FOR_DECODE */
	public const ONLY_FOR_DECODE = HintType::ONLY_FOR_DECODE;

	/**
	 * Create a new serialization class type hint.
	 * @param class-string<T>|Hint<T> $parent
	 * @param HintType $modifier A hint modifier
	 */
	public function __construct(
		/** @var class-string<T>|Hint<T> */
		public readonly string|Hint $parent,
		public readonly HintType $modifier = HintType::DEFAULT,
	) {
	}

	/**
	 * Helper function to create nested hints.  For example, the
	 * `Foo[][]` type can be created as
	 * `Hint::build(Foo::class, Hint:LIST, Hint::LIST)`.
	 *
	 * Note that, in the grand (?) tradition of C-like types,
	 * modifiers are read right-to-left.  That is, a "stdClass containing
	 * values which are lists of Foo" is written 'backwards' as:
	 * `Hint::build(Foo::class, Hint::LIST, Hint::STDCLASS)`.
	 *
	 * @phan-template T
	 * @param class-string<T>|Hint<T> $classNameOrHint
	 * @param HintType ...$modifiers
	 * @return class-string<T>|Hint<T>
	 */
	public static function build( string|Hint $classNameOrHint, HintType ...$modifiers ) {
		if ( count( $modifiers ) === 0 ) {
			return $classNameOrHint;
		}
		$last = array_pop( $modifiers );
		return new Hint( self::build( $classNameOrHint, ...$modifiers ), $last );
	}

	public function __toString(): string {
		$parent = strval( $this->parent );
		return "{$this->modifier->name}({$parent})";
	}
}
