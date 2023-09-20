[![Latest Stable Version]](https://packagist.org/packages/wikimedia/json-codec) [![License]](https://packagist.org/packages/wikimedia/json-codec)

JsonCodec
=====================

Interfaces to serialize and deserialize PHP objects to/from JSON.

Additional documentation about this library can be found on
[mediawiki.org](https://www.mediawiki.org/wiki/JsonCodec).


Usage
-----

To make an object serializable/deserializable to/from JSON, the
simplest way is to use the `JsonCodecableTrait` and implement two
methods in your class, `toJsonArray()` and the static method
`newFromJsonArray()`:
```php
use Wikimedia\JsonCodec\JsonCodecable;

class SampleObject implements JsonCodecable {
	use JsonCodecableTrait;

	/** @var string */
	public string $property;

	// ....

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return [
			'property' => $this->property,
		];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): SampleObject {
		return new SampleObject( $json['property'] );
	}
}
```
A slightly more complicated version of this example can be found in
[`tests/SampleObject.php`](./tests/SampleObject.php).

If your class requires explicit management -- for example, object
instances need to be created using a factory service, you can
implement `JsonCodecable` directly:
```php
use Wikimedia\JsonCodec\JsonCodecable;

class ManagedObject implements JsonCodecable {
	public static function jsonClassCodec( ContainerInterface $serviceContainer ) {
		$factory = $serviceContainer->get( 'MyObjectFactory' );
		return new class( $factory ) implements JsonClassCodec {
			// ...
			public function toJsonArray( $obj ): array {
				// ...
			}
			public function newFromJsonArray( string $className, array $json ): ManagedObject {
				return $this->factory->create( $json[....] );
			}
		};
	}
}
```
A full example can be found in
[`tests/ManagedObject.php`](./tests/ManagedObject.php).

Note that array returned by `toJsonArray()` can include other
`JsonCodecable` objects, which will be recursively serialized.
When `newFromJsonArray` is called during deserialization, all
of these recursively included objects will already have been
deserialized back into objects.

To serialize an object to JSON, use [`JsonCodec`](./src/JsonCodec.php):
```php
use Wikimedia\JsonCodec\JsonCodec;

$services = ... your global services object, or null ...;
$codec = new JsonCodec( $services );

$string_result = $codec->toJsonString( $someComplexValue );
$someComplexValue = $codec->newFromJsonString( $string_result );
```

In some cases you want to embed this output into another context,
or to pretty-print the output using non-default `json_encode` options.
In these cases it can be useful to have access to methods which
return or accept the array form of the encoding, just before
json encoding/decoding:
```php
$array_result = $codec->toJsonArray( $someComplexValue );
var_export($array_result); // pretty-print
$request->jsonResponse( [ 'error': false, 'embedded': $array_result ] );

$someComplexValue = $codec->fromJsonArray( $data['embedded'] );
```

Running tests
-------------

```
composer install
composer test
```

History
-------
The JsonCodec concept was first introduced in MediaWiki 1.36.0 ([dbdc2a3cd33](https://gerrit.wikimedia.org/r/c/mediawiki/core/+/641575/)). It was
split out of the MediaWiki codebase and published as an independent library
during the MediaWiki 1.41 development cycle, with changes to the API.

---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/json-codec/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/json-codec/license.svg
