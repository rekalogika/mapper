# rekalogika/mapper

An object mapper (also called automapper) for PHP and Symfony. It maps an object
to another object. Primarily used to map an entity to a DTO, but also useful for
other mapping purposes.

Full documentation is available at [rekalogika.dev/mapper](https://rekalogika.dev/mapper/).

## Features

* Automatically lists the properties of the source and target, detects their
  types, and maps them accordingly.
* By default, does not attempt to circumvent your class constraints. Reads only
  from and writes only to public properties, getters, setters. Does not
  instantiate objects without their constructor.
* Constructor initialization.
* Handles nested objects.
* Handles recursion and circular references.
* Inheritance support. Maps to abstract classes and interfaces using an
  inheritance map attribute.
* Reads the type from PHP type declaration and PHPDoc annotations, including
  the type of the nested objects.
* Handles `array`, `ArrayAccess` and `Traversable` objects, and the mapping
  between them.
* Lazy stream mapping if the target is type-hinted as `Traversable`. Consumes
  less memory & avoids hydrating a Doctrine collection prematurely.
* In addition, when the target is `Traversable` and the source is a `Countable`,
  then the target will also be a `Countable`. With an extra-lazy Doctrine
  Collection, the consumer will be able to count the target without causing a
  full hydration of the source.
* Manual mapping using a class method.
* Easy to extend by creating new transformers, or decorating the existing ones.
* Match classes using attributes in your transformers, in addition to using
  class names.
* Helpful exception messages.
* Console commands for debugging.

## Future Features

* Option to map to or from different property name? (seems to be a popular
  feature, but I prefer the native OOP way of doing it)
* Option to read & write to private properties?
* Data collector and profiler integration.

## Installation

```bash
composer require rekalogika/mapper
```
## Usage

In Symfony projects, simply autowire `MapperInterface`. In non Symfony projects,
instantiate a `MapperFactory`, and use `getMapper()` to get an instance of
`MapperInterface`.

To map objects, you can use the `map()` method of `MapperInterface`.

```php
use Rekalogika\Mapper\MapperInterface;

/** @var MapperInterface $mapper */

$book = new Book();
$result = $mapper->map($book, BookDto::class);

// or map to an existing object

$book = new Book();
$bookDto = new BookDto();
$mapper->map($book, $bookDto);
```

## Documentation

[rekalogika.dev/mapper](https://rekalogika.dev/mapper/)

## License

MIT
