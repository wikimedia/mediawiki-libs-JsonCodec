# Release History

## x.x.x (not yet released)
* Add additional protected methods to JsonCodec to allow subclasses to
  further customize the encoding used.

## 2.1.0 (2023-10-02)
* Allow ::jsonClassHintFor() to return a class-string suffixed with
  `[]` to indicate a list or homogeneous array of the given type.

## 2.0.0 (2023-10-02)
* JsonCodec::addCodecFor() is added to provide the ability to
  serialize/deserialize objects which don't implement JsonCodecable.

## 1.0.0

* Initial release.
