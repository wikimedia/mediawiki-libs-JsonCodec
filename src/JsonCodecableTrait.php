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
 */

namespace Wikimedia\JsonCodec;

use stdClass;

trait JsonCodecableTrait {

	/**
	 * Implement JsonSerializable using JsonCodecable methods.
	 * @return array
	 */
	public function jsonSerialize(): array {
		'@phan-var JsonCodecable $this';
		$json = $this->toJsonArray();
		$json[JsonConstants::TYPE_ANNOTATION] = get_class( $this );
		return $json;
	}

	/** Implement JsonDeserializable using JsonCodecable methods.
	 * @param array $json
	 * @return JsonCodecable
	 */
	public static function jsonDeserialize( array $json ) {
		$class = $json[JsonConstants::TYPE_ANNOTATION];
		if ( $class !== stdClass::class &&
			 !( class_exists( $class ) && is_subclass_of( $class, JsonCodecable::class ) )
		) {
			throw new \InvalidArgumentException( "Invalid target class {$class}" );
		}

		if ( $class === stdClass::class ) {
			unset( $json[JsonConstants::TYPE_ANNOTATION] );
			return (object)$json;
		}
		return $class::newFromJsonArray( $json );
	}
}
