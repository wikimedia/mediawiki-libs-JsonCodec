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

use InvalidArgumentException;

/**
 * Deserializes things from JSON.
 */
interface JsonDeserializer {

	/**
	 * Restore an instance of simple type or JsonDeserializable subclass
	 * from the JSON serialization. It supports passing array/object to
	 * allow manual decoding of the JSON string if needed.
	 *
	 * @phpcs:ignore MediaWiki.Commenting.FunctionComment.ObjectTypeHintParam
	 * @param array|string|object $json
	 * @param class-string|null $expectedClass What class to expect in deserialization.
	 *   If null, no expectation. Must be a descendant of JsonDeserializable.
	 * @throws InvalidArgumentException if the passed $json can't be deserialized.
	 * @return mixed
	 */
	public function deserialize( $json, string $expectedClass = null );

	/**
	 * Helper to unserialize an array of JsonDeserializable instances or simple types.
	 * @param array $array
	 * @return array
	 */
	public function deserializeArray( array $array ): array;
}
