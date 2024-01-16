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

## Rationale, or Why Create Another Mapper?

We developed a project that during its planning phase we determined that it
would be beneficial to integrate an automapper into the architecture. We looked
around and found some potential automappers, and decided to go ahead with the
planned architecture.

We first tried
[AutoMapper-Plus](https://github.com/mark-gerarts/automapper-plus) and
immediately ran into the issue that it reads and writes directly to properties,
including private properties, which is unacceptable to our purposes. For
example, we store monetary values as integers in the object, and convert them
from and to `Money` objects in the getter and setter. Using this mapper it would
get the raw integers, not the `Money` objects. We also feel it violates the
principles of encapsulation. However, this was not a blocker as it supports a
custom property accessor, so we resolved this issue by creating an adapter that
uses Symfony PropertyAccess component.

With AutoMapper-Plus, users are expected to create a mapping configuration for
each mapping pair. It has an automatic creation of mappings, but it seems to
only work for simple mapping. Also, all of my entities and DTOs are typed, but I
need to create a mapping for every non-trivial pair, despite the type
information is there in the involved classes. Custom mapper is available, but it
does not give me the main mapper, so if a mapping pair uses a custom mapper, I'm
responsible for mapping everything, including the nested objects myself, because
the option to delegate to the main mapper is not available. There was another
case that was simply not possible to accomplish, I don't remember what it was,
but it forced us to switch to another mapper overnight.

Our next mapper was [Jolicode
Automapper](https://github.com/jolicode/automapper), formerly known as [Jane
Automapper](https://github.com/janephp/automapper). It behaved as expected,
there were no big surprises, and there was very little to complain about its
behavior. It should also be very fast, as it compiles its mapping code to PHP
files. The problem was error handling. When an error occurred in the compiled
mappers, it was usually a `TypeError`. It was difficult to debug, and even more
difficult to resolve the problem, addressing the problem requires the skill of
working with AST. However, we found that the problems were deployment errors
(usually forgetting to clear the cache), some edge cases (easy to work around),
or bugs in the mapper. We did contributed some fixes back to the project.

The second problem was that the mapper was difficult to extend. Adding a new
transformer requires the skill of working with AST, and there was no option to
do a mapping using plain old PHP code that you write yourself. Our team was not
happy with this fact. We hit a brick wall when a new requirement surfaced that
requires the mapper to target an abstract class, a feature that was not
supported by the mapper. We figured it would be easier for us to spend a week
creating our own mapper from scratch using our experiences with the other
mappers, and here we are.

Other mappers that were considered:

[MicroMapper](https://github.com/SymfonyCasts/micro-mapper/) is a mapper that
requires you to write the mapping code yourself. It is all manual work, but
still working within the mapping framework, and should be suitable for our
purpose, as long as we are willing to write the mapping code ourselves. The
mapping code also supports delegating to the main mapper, unlike
AutoMapper-Plus. However, at this point, we were way past of contemplating
whether to do it manually, so we did not consider it further.

[Pull request for a future Symfony Mapper](https://github.com/symfony/symfony/pull/51741).
In the form that I saw it, it was too simplistic, and does not provide any
extension points. I (@priyadi) did provide some feedback in the pull request.
## License

MIT
