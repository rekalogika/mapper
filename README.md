# rekalogika/mapper

An object mapper (also called automapper) for PHP and Symfony. It maps an object
to another object. Primarily used to map an entity to a DTO, but also useful for
other mapping purposes.

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
## License

MIT
