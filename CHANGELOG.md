# CHANGELOG

## 0.7.0

* refactor(`ObjectToObjectTransformer`): Refactor for future extension.
* feat: Target object can now be a lazy-loading proxy.

## 0.6.7

* fix(`TraceData`): Do not throw exception on missing data.
* fix(`MainTransformer`): Improve error reporting on circular references.
* fix(`ObjectCache`): Change backing store to `WeakMap`.
* feat(`EagerPropertyResolver`): Required for future lazy feature.
* perf(`ObjectToObjectTransformer`): Handle null to scalar transformation
  internally.

## 0.6.6

* dx(`SubMapperInterface`): `map()` now accepts null input, that will return
  null.

## 0.6.5

* fix(`ObjectToObjectMetadataFactory`): Remove remnants.
* fix(`SubMapperInterface`): Context is now null by default.

## 0.6.4

* fix: Hide toolbar icon if no mappings are recorded.

## 0.6.3

* fix: Add check if warmed files exist.
* fix(`MapperDataCollector`): Fix problem is no mappings is recorded.

## 0.6.2

* feat: Data collector.
* feat: Web profiler bundle integration.
* fix: Fix web profiler HTML.
* refactor: Namespace for `ArrayLikeMetadata` & `ObjectToObjectMetadata`.
* refactor(`ObjectToObjectMetadata`): Made immutable.
* feat(`ObjectToObjectMetadata`): In debug mode, check if the involved classes
  have changed.

## 0.6.1

* fix: Add caching for `ObjectMapperTable`.
* perf: Use `ObjectMapperResolver` to resolve object mapper.
* perf: Incorporate inheritance mapping logic into `ObjectToObjectTransformer`.

## 0.6.0

* style: Remove remnants.
* test: Add PHPStan unused public.
* refactor(`PropertyMapper`): `$sourceClass` is redundant & removed.
* refactor(`PropertyMapper`): If `property` is missing, use the method name,
  stripping the leading 'map' and lowercasing the first letter. Example:
  `mapName` will map to the property `name`.
* feat(`PropertyMapper`): Option to add `Context` & `MainTransformerInterface`
  as extra arguments to the property mapper.
* refactor(`SubMapper`): Move to its own namespace.
* refactor(`SubMapper`): Use a factory to create sub mappers.
* refactor(`ObjectCache`): Remove references to `Context`.
* refactor(`SubMapper`): Add `cache()` method.
* refactor: Rename `MapperPass` to `RemoveOptionalDefinitionPass`
* refactor: Move implementation under their own namespaces.
* refactor(`AsPropertyMapper`): Move to top attribute namespace.
* refactor: Rename `PropertyMapperServicePointer` to `ServiceMethodSpecification`.
* refactor: Rename `PropertyMapper` namespace to `CustomMapper`.
* refactor: Genericize `ServiceMethodSpecification` & `ServiceMethodRunner`.
* fix(`ObjectToObjectMetadataFactory`): Handle `PropertyWriteInfo::TYPE_NONE`.
* feat: `ObjectMapper`
* refactor: Compiler pass namespace.
* refactor(`CompilerPass`): Reduce code duplication.
* feat(`SubMapper`): Now available as additional argument in `PropertyMapper` &
  `ObjectMapper`.
* test: Migrate tests to use `FrameworkBundle`.

## 0.5.26

* style(`ReaderWriter`): Cleanup.
* test: Test mapping `Money` with integer backing.
* style: Remove Property Access.

## 0.5.25

* fix(`ObjectCache`): Enum should not be cached.
* test: Add more tests for object keys.
* fix(`ObjectToObjectTransformer`): Throw exception when trying to map internal
  classes.
* fix(`ContextAwareExceptionTrait`): Improve exception message.
* refactor(`PropertyMapping`): Include information from property read/write
  info.
* feat: Add support for adder methods.
* refactor: Remove `AdderRemoverProxy` from support list, use `ArrayAccess`
  instead.
* perf: Read & write properties directly without Property Access.

## 0.5.23

* refactor(`TraversableTransformerTrait`): Refactor for clarity.
* refactor(`ArrayLikeTransformerTrait`): Rename for clarity.
* refactor(`ArrayLikeMetadata`): Add `$type`.
* refactor(`PropertyMapping`): Add `$sourceTypes`.
* refactor(`TraversableToArrayAccessTransformer`): Refactor for clarity.
* refactor(`ArrayLikeTransformerTrait`): Cleanup.
* refactor(`MainTransformer`): Add `$sourceType` so we can preserve generics
  information obtained from the source class.
* refactor(`ArrayLikeMetadata`): Refactor for future extension.
* refactor(`ArrayLikeMetadata`): Also inject source type information.
* perf: Use xxh128 hash for cache keys.
* refactor(`ArrayLikeMetadata`): Add more properties.
* feat(`TraversableToArrayAccessTransformer`): Add lazy capability.

## 0.5.22

* refactor: Rename `ObjectStorage` to `HashTable`.
* refactor(`HashTable`): Supports all variable types.
* perf: Make `ArrayLikeMetadata` cacheable.

## 0.5.21

* feat: Supports object with non `int|string` keys.
* refactor(`TraversableTransformerTrait`): Refactor properties to a metadata
  object.
* feat: Fix `SplObjectStorage` iterator by wrapping it in
  `SplObjectStorageWrapper`.
* refactor(`Path`): Cleanup.
* feat: Use `(key)` in the path for transformation in the keys of array-like
  objects.

## 0.5.20

* perf(`ObjectToObjectTransformer`): Do simple scalar to scalar mapping without
  delegating to the `MainTransformer`.
* refactor(`ObjectToObjectTransformer`): Reduce code duplication.
* refactor: Reduce code duplication in `TraversableToArrayAccessTransformer` &
  `TraversableToTraversableTransformer`.

## 0.5.19

* perf: Move instantiable check to `ObjectMappingResolver`.
* refactor: Do not use `array_intersect` to determine object mapping.
* dx(`ObjectMapping`): Make it mutable.
* refactor(`ObjectToObjectMetadata`): Class & namespace renames for clarity.
* refactor(`ObjectToObjectMetadata`): Refactor for future extension.
* refactor(`ObjectToObjectMetadata`): Add `doReadSource` flag.
* feat: Add `PropertyMapper` for customizing property mapping in object to
  object transformation.

## 0.5.18

* refactor(`ClassMethodTransformer`): Move to Transformer namespace.
* revert(`TransformerRegistry`): A transformer might apply twice to the same
  source-target pair.
* style: Use `MarkdownLikeTableStyle` in console commands.
* perf(`Context`): Improve context creation.
* feat: Add `NormalizerContext` & `DenormalizerContext` to provide the context
  for normalizers & denormalizers.
* dx(`Context`): Change `create()` to use variable-length arguments.

## 0.5.17

* fix: Fix static analysis issues.
* perf(`TransformerRegistry`): Optimize sorting.
* perf(`TransformerRegistry`): Optimize caching.
* feat: Symfony Uid support.
* build: Remove `SymfonyUidTransformer` if Symfony UID is not installed.

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
