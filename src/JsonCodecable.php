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

/**
 * Classes implementing this interface support round-trip JSON
 * serialization/deserialization using the JsonCodec.
 *
 * The resulting JSON must be annotated with class information for
 * deserialization to work.  Use the JsonCodecableTrait when
 * implementing classes which annotates the JSON automatically.
 *
 * @see JsonCodec
 * @see JsonCodecableTrait
 * @since 1.36
 */
interface JsonCodecable {

	/**
	 * Returns a JSON array representing the contents of this class, that
	 * can be deserialized with the corresponding newFromJsonArray() method.
	 *
	 * The returned array can contain other JsonCodecables as values;
	 * the JsonCodec class will take care of encoding values in the array
	 * as needed, as well as annotating the returned array with the class
	 * information needed to locate the correct ::newFromJsonArray()
	 * method during deserialization.
	 *
	 * @return array A Json representation of this instance
	 */
	public function toJsonArray(): array;

	/**
	 * Creates a new instance of the class and initialized it from the
	 * $json array.
	 * @param array $json
	 * @return JsonCodecable
	 */
	public static function newFromJsonArray( array $json );
}
