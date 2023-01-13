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
 * Helper class to serialize/unserialize things to/from JSON.
 *
 * @stable to type
 * @since 1.36
 * @package MediaWiki\Json
 */
interface JsonCodec extends JsonDeserializer, JsonSerializer {

	/**
	 * Checks if the $value is JSON-serializable (contains only scalar values)
	 * and returns a JSON-path to the first non-serializable property encountered.
	 *
	 * @param mixed $value
	 * @param bool $expectDeserialize whether to expect the $value to be deserializable with JsonDeserializer.
	 * @return string|null JSON path to first encountered non-serializable property or null.
	 * @see JsonDeserializer
	 * @since 1.36
	 */
	public function detectNonSerializableData( $value, bool $expectDeserialize = false ): ?string;
}