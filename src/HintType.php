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

enum HintType {
	/**
	 * The default class hint behavior: an exact match for class name,
	 * and the serialization for an object will always use curly
	 * braces `{}` but the return value from `::toJsonArray()` will
	 * always be an array.  This requires adding an explicit
	 * `JsonCodec::TYPE_ANNOTATION` element to lists even if proper
	 * type hints are supplied.
	 */
	case DEFAULT;
	/**
	 * A list of the hinted type.
	 */
	case LIST;
	/**
	 * A map of the hinted type.  The value is a stdClass object with
	 * string keys and property values of the specified type.
	 */
	case STDCLASS;
	/**
	 * Prefer to use square brackets to serialize this object, when
	 * possible. Not compatible with `ALLOW_OBJECT`.
	 */
	case USE_SQUARE;
	/**
	 * Tweak the return type of `JsonCodec::toJsonArray()` to return
	 * a `stdClass` object instead of array where that makes it possible
	 * to generate curly braces instead of adding an extra
	 * `JsonCodec::TYPE_ANNOTATION` value.  Not compatible with `USE_SQUARE`.
	 */
	case ALLOW_OBJECT;
	/**
	 * The value is an `instanceof` the hinted type, and the
	 * `JsonClassCodec` for the hinted type will be able to
	 * deserialize the object.  This is useful for tagged objects of
	 * various kinds, where a superclass can look at the json data to
	 * determine which of its subclasses to instantiate.  Note that in
	 * this case hints will be taken from the superclass's codec.
	 */
	case INHERITED;
	/**
	 * Mark the supplied hint for use only during deserialization
	 * (`JsonCodec::newFromJsonArray`).  The full class information
	 * will still be recorded during serialization (`::toJsonArray`).
	 * This allows the hint to be used for forward-compatibility
	 * with a future release that will utilize implicit class
	 * information, without harming backward-compatibility by
	 * (yet) omitting the explicit class information.
	 */
	case ONLY_FOR_DECODE;
}
