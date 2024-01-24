# CHANGELOG

## 0.5.16

* perf: Optimize `guessTypeFromVariable`
* perf: Optimize `Context` & `ObjectCache`
* perf: Change 'simpletypes' cache to array.
* perf: Use our `PropertyAccessLite` instead of Symfony's.
* fix(`CachingObjectMappingResolver`): Safeguard
* perf: Use flyweight pattern for `TypeFactory`
* perf(`TypeCheck`): Improve `isVariableInstanceOf`.
* perf(`ScalarToScalarTransformer`): Optimize.
* perf: Remove type guesser from `TypeResolver`.
* perf(`ObjectCache`): Optimize using plain arrays.
* perf: Optimize `ObjectCache` & `Context`.
* perf(`TransformerRegistry`): Cache `TransformerInterface` instances.
* perf: Optimize `ObjectCache` & `Context`.
* perf(`TypeFactory`): Optimize `objectWithKeyValue`.
* perf(`CachingTypeResolver`): Use `rawurlencode` instead of `md5`.

## 0.5.15

* perf: Add caching for `TransformerRegistry`.

## 0.5.14

* fix: Add missing deps to `composer.json`

## 0.5.13

* docs: Add rationale.
* fix: Add variance safeguard.
* dx: Clarity.
* style: Exception messages.
* feat: Uses transformer class names as service IDs for easier decoration.
* refactor: Move mapping logic in `ObjectToObjectTransformer` to its own
  service.
* refactor: Move more mapping logic to `ObjectMappingResolver`.
* perf: Add caching for `ObjectMappingResolver`.

## 0.5.12

* test: Reorganize test namespaces.
* fix: Fix path forming.
* fix: Add type checking for variance in `TypeMapping` and `MappingEntry`.
* refactor: Refactor `Transformer` for clarity.
* refactor: Transformer is now lazy-loaded.

## 0.5.11

* feat: Make the fourth argument optional in `TryPropertyCommand`.
* fix(`TransformerRegistry`): Non-object target type is always invariant.
* dx(`SearchResult`): Now an `ArrayAccess`.
* feat(`TryPropertyCommand`): Improve output.
* dx(`TypeCheck`): Now accept `MixedType` as an argument.
* test: Mapping test first version.
* refactor: rename `RefuseToHandleException` to `RefuseToTransformException`.
* perf(`CopyTransformer`): Move identity check to the beginning.
* fix(`MainTransformer`): Fix possible bug involving an existing target.
* fix(`MainTransformer`): Make sure the target has the same type as the target
  type.
* feat(`TraversableToArrayAccessTransformer`): Now supports `ArrayCollection` &
  `ArrayIterator`.
* test: Assorted tests.
* fix: Remove iterating object key because not supported in PHP.
* test: Assorted tests.

## 0.5.10

* fix: `NullTransformer` bug.
* fix(`CachingMappingFactory`): Cache result in memory.
* fix(`CachingMappingFactory`): Skip the cache if in debug mode.
## 0.5.9

* fix: Service definition for `TryPropertyCommand`.
* feat: All exceptions now accept `$context` variable for more useful
  error messages.
* feat: Context methods now accept `$context` variable, and pass it to
  exceptions.
* feat: Main transformer's exception now accepts `$context` variable.
* feat: More useful `TransformerReturnsUnexpectedValueException` exception message.
* feat: If a transformer throws `RefuseToHandleException`, the `MainTransformer`
  will try the next suitable transformer.
* style: Remove unused vars.
* feat: Transformers now have the option to have an invariant target type.
* fix: Wrong service id for `CopyTransformer`.
* fix: Remove cache file & regenerate it if it is corrupt.

## 0.5.8

* fix: PropertyAccessor `UninitializedPropertyException` error now is regarded
  as null.****
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
