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
* Override the mapping logic using a custom property mapper.
* Constructor initialization.
* Handles nested objects.
* Handles recursion and circular references.
* Inheritance support. Maps to abstract classes and interfaces using an
  inheritance map attribute.
* Reads the type from PHP type declaration and PHPDoc annotations, including
  the type of the nested objects.
* If possible, target objects are lazy-loaded. The mapping does not take place
  until the target is accessed.
* Attempts to detect identifier properties on the source side. Those properties
  on the target side will be mapped eagerly, and will not trigger the hydration.
  Thus, API Platform will be able to generate IRIs without causing the hydration
  of the entire object graph.
* Handles the mapping between `array` or array-like objects, as well as using an
  adder method.
* Handles non-string & non-integer keys in array-like objects, including
  `SplObjectStorage`.
* Lazy loading & lazy stream mapping with collection types. Consumes less memory
  & avoids hydrating a Doctrine collection prematurely.
* With lazy loading, if the source is a `Countable`, then the target will also
  be a `Countable`. With an extra-lazy Doctrine Collection, the consumer will be
  able to count the target without causing a full hydration of the source.
* Manual mapping using a class method.
* Easy to extend by creating new transformers, or decorating the existing ones.
* Match classes using attributes in your transformers, in addition to using
  class names.
* Helpful exception messages.
* Console commands for debugging.
* Data collector and profiler integration.

## Future Features

* Option to read & write to private properties.
* Migrate engine to `symfony/type-info`.

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
