# CHANGELOG

## 0.5.9

* fix: Service definition for `TryPropertyCommand`.

## 0.5.8

* fix: PropertyAccessor `UninitializedPropertyException` error now is regarded
  as null.
* fix: Transformer `SearchResult` was not properly ordered.
* feat: `TryProperty` command, or `rekalogika:mapper:tryproperty` in console.

## 0.5.7

* fix: Improve exception message.

## 0.5.5

* docs: Add link to documentation website.
* refactor: Consolidate boilerplate code for getting the `ObjectCache`.
* refactor: Move transformer query logic to `TransformerRegistry`.
* refactor: Mover more logic to `TransformerRegistry`.
* refactor: Move `MainTransformer` to its own namespace.
* refactor: Refactor exception.
* feat: Add attribute matching.
* refactor: Simplify object caching.
* refactor: Remove `$context` from `MapperInterface`
* chore: Fix static analysis issues.
* refactor: Change context array to `Context` object.
* refactor: Move `Context` to its own namespace.
* style(Context): Rename `set` to `with` and `remove` to `without`.
* refactor: Reintroduce `Context` to `MapperInterface`.
* feat: Inheritance support.

## 0.5.4

* perf: Add a caching layer for `TypeResolver`
* feat: `TraversableToTraversableTransformer` now accepts `Generator` as a
  target type
* feat: Constructor arguments
* test: Custom transformer
* refactor: Move `MixedType` to contracts
* refactor: Move standalone `MapperFactory` under MapperFactory namespace
* refactor: Simplify `MapperInterface`
* test: Fix tests due to refactor
* refactor: Move deprecated facade to Facade namespace
* refactor: `MainTransformerInterface` now only accept array `$targetType`
* refactor: `TransformerInterface` now accepts null `$targetType` to signify
  mixed type.
* refactor: Remove deprecated facade.
* feat: Add `CopyTransformer` to handle mixed to mixed mapping.
* feat: `TraversableToTraversableTransformer` now gives a `Countable` result
  if the source is `Countable`.
* revert: Revert support for `Generator` target type. Impossible to have a
  `Countable` result.
* docs: Improve documentation
* fix: Change `ObjectCache` to use `WeakMap`. Should improve memory usage.
* feat: Method mapper

## 0.5.3

* refactor: Use `MappingFactoryInterface` everywhere instead of `MappingFactory`
* perf: Move some `TypeUtil` methods to `TypeResolver` for optimization
  opportunities
* refactor: use `ObjectCacheFactory` to generate `ObjectCache` instances
* chore: Update `MapperFactory` to reflect framework usage
* perf: Use property info caching in non-framework usage
* perf: Add mapping caching
* style: Change console command to use `rekalogika` prefix

## 0.5.2

* feat: Support Symfony 7
* fix: Fix service definition for `MapperFactory`

## 0.5.0

* build: Initial release
