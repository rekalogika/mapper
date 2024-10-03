# CHANGELOG

## 1.9.3

* fix: null values were skipped if the target allows dynamic properties

## 1.9.2

* style: remove unused immutability check
* refactor: separate caching
* fix: no longer throws exception if the target has no setter

## 1.9.1

* test: property/object mapper second argument with unalterable object
* refactor: saner metadata factory organization
* fix: target property that is unalterable & not mutable by the host should be
  skipped
* build: use dotenv for test environment

## 1.9.0

* feat: target properties with `Collection` & `ReadableCollection` typehint will
  now be lazy-loaded
* chore: rector run
* chore: refactor `ObjectToObjectMetadataFactory` for clarity
* chore: refactor `PropertyMappingResolver`
* chore: cleanup
* feat: property path support in `Map` attributes
* fix: writing using property path
* fix: data collector output for property path
* chore: naming, etc
* test: property path negative test
* fix: mapping from `stdClass` to object with mandatory constructor arguments
* feat: variadic setter & constructor arguments support
* feat: string to `UnitEnum` transformation
* feat: datetime format & timezone conversion
* refactor: genericize attribute handling
* feat: attributes now affects the transformation of collection members
* fix: date time format was not recognized
* test: attribute with missing class
* fix: multiple attributes found in inheritance chain now correctly handled
* feat: save all attributes in metadata, not just our attributes
* feat: datetime transformation to int & float
* feat: class attributes are now stored in the metadata
* chore: rector run
* feat: collect attributes if using property path mapping
* fix: mapping to property without setter wasn't working
* fix: mapping string with timezone to datetime should set the timezone on the
  target
* perf: don't call the setter if the value doesn't change
* feat: `Eager` attribute to disable proxying on target classes
* perf: skip property if setting or mutating is impossible
* chore: rector run
* test: setting new instance to a target without setter
* refactor: consolidate property & property path metadata into single interface
* refactor: consolidate source & target metadata classes
* refactor: add general class metadata
* feat: support for immutable setters
* test: immutable setter without setter on parent object error
* test: immutable setter now works using 'wither' method
* fix: readonly objects no longer assumed to be value objects
* feat: `ValueObject` attribute to explicitly mark classes as value objects
* feat: heuristics to detect value objects
* test: integrate profiler with phpunit
* feat: timing using Symfony stopwatch
* test: test different setter return types
* feat: support for immutable adder and remover
* feat: optional second argument for getting the existing target value
* refactor: separate dynamic properties determination to dedicated class
* refactor: add an abstraction for property read/write info extractor
* refactor: separate attributes extraction to dedicated class
* refactor: encapsulate attributes in a class
* refactor: separate value object determination to a dedicated class
* fix: improve value object detection heuristics
* chore: change hashing algorithm from sha256 to xxh128
* feat: remove `hasExistingTarget` tag attribute from object & property mapper
  service definition.
* chore: rename term from 'value object' to 'unalterable' to reduce ambiguity
* build: makefile & workflow schedule
* fix: if array is list, don't match the targets using their keys
* test: more unalterable tests

## 1.8.0

* feat: use error handler when reading source classes with dynamic properties
* feat: handle magic `__get()` on the source side
* feat: handle magic `__set()` on the target side
* feat: allow attaching attribute to getters instead of the property
* perf: avoid guessing source type for the second time
* feat: add `Map` attribute to map from different source property
* feat: `Map` now supports class inheritance
* feat: `Map` now supports reversed mapping
* refactor: refactor to `PropertyMappingResolver` for clarity
* refactor: refactor for clarity
* refactor: remove unneeded `initializableTargetPropertiesNotInSource`
* fix: `Map` class property is now inheritance aware
* feat: `AllowDelete` now can be attached to remover methods to take effect
* style: naming, etc

## 1.7.0

* fix: spaceless twig filter is deprecated
* test: modernize tests
* feat: union types support in object mappers
* feat: union types support in property mappers
* fix: mapping unset source property to not null target property

## 1.6.1

* fix: remove phpstan config remnant
* fix: Doctrine MongoDB ODM proxy class resolving (issue [#106](https://github.com/rekalogika/mapper/issues/106))

## 1.6.0

* feat: add `AllowTargetDelete`, similar to `AllowDelete` but defined on the
  source side
* fix: PHP 8.4 compatibility

## 1.5.7

* deps: allow updates to property-info

## 1.5.6

* fix: compatibility with `symfony/dependency-injection` 7.1.4

## 1.5.5

* build: add rector
* build: add `Override` attributes where applicable
* chore: satisfy static analysis

## 1.5.4

* fix: exclude `symfony/property-info` version 7.1.2 and 6.4.9,
  https://github.com/symfony/symfony/issues/57634
* build: satisfy latest phpstan

## 1.5.2

* refactor(`ObjectMapperTable`): simplification

## 1.5.1

* build: add github-actions to dependabot
* build: update php-cs-fixer
* refactor: use `ClassUtil::getAllClassesFromObject()` for `class_parents()` +
  `class_implements()`

## 1.5.0

* feat: utilize `InheritanceMap` on the source side to determine the target
  class
* fix: uuid packages are now not required

## 1.4.0

* feat: `ramsey/uuid` support

## 1.3.0

* test: add tests for mapping to objects with existing value
* fix: fix: Setter does not get called if the property is also in the
  constructor
* feat: option to remove missing members from the target object

## 1.2.0

* feat: add `IterableMapperInterface` for mapping iterables
* feat: add `IterableMapperInterface` getter to the non-framework factory

## 1.1.2

* fix: property info caching if another bundle is decorating cache ([#47](https://github.com/rekalogika/mapper/issues/47))

## 1.1.1

* fix: Fix missing `kernel.reset` tags.
* fix: Proxy generation under `opcache` and/or `classmap-authoritative`

## 1.1.0

* feat: `PresetTransformer`.
* fix: Typo in `RemoveOptionalDefinitionPass`
* feat: Supports dynamic properties (including `stdClass`) on the source side.
* fix(`Mapper`): Fix typehint.
* test: test array cast to object mapping
* feat(`Context`): `with()` not accepts multiple argument.
* build: Deinternalize `ObjectCacheFactory`
* fix(`PresetMapping`): Support proxied classes, add tests.
* fix: Disallow proxy for objects with dynamic properties, including `stdClass`.
* feat: Dynamic properties (`stdClass` & co) on the target side.
* feat: Deprecate `ArrayToObjectTransformer` & `ObjectToArrayTransformer`,
  replace with `ArrayObjectTransformer`.
* fix: Fix dynamic properties in Symfony profiler panel.
* fix: Fix `PresetTransformer`.
* fix: mapping to object extending `stdClass` to property with no setter.
* feat: `stdClass` to `stdClass` mapping should work correctly.
* feat: Mapping to existing values in a dynamic property.
* perf(`ObjectToObjectTransformer`): Prevent delegating to `MainTransformer` if
  the current value in a dynamic property is a scalar.
* feat(`PresetMappingFactory`): Add `fromObjectCache()` and `fromObjectCacheReversed()`.
* chore: Simplify remembering mapper.
* refactor: Deprecate serializer context.
* feat: null to `Traversable` or `ArrayAccess` is now handled & returns empty.
* chore: Add `readonly` or implement `ResetInterface` to applicable classes.

## 1.0.0

* No changes.

## 0.10.2

* fix: Handle cases where transformed key is different from the original.

## 0.10.1

* fix: Deprecate `MapFromObjectInterface` & `MapToObjectInterface`.
* refactor(`MainTransformer`): Move to implementation namespace.
* refactor: Move proxy creation code to `ProxyFactory`.
* feat(`SubMapperInterface`): Add `createProxy()`.
* refactor(`ProxyGeneratorInterface`): Remove `ProxySpecification`.

## 0.10.0

* fix(`composer.json`): Change PHP require to `>=8.2`.
* refactor(`Mapper`): Move under implementation namespace.
* refactor: Rename `ArrayInterface` to `CollectionInterface`.
* refactor(`MapperFactory`): Move to top namespace.
* refactor(`Transformer`): Move implementation to its own namespace.

## 0.9.1

* perf(`TypeResolver`): Optimize `getSimpleTypes`.
* fix(`MainTransformer`): Reduce GC interval to 500.
* build: Require PHP 8.2.
* chore: Add `final`, `readonly`, and `internal` if applicable to all classes.

## 0.9.0

* fix(`MapperInterface`): Fix type-hint mismatch.
* test: Add hook to override the default `Context`.
* test: Test with and without scalar short circuit.
* fix: Null value to nullable target transformation (issue #4).

## 0.8.1

* fix(`DoctrineProxyGenerator`): Remove if Doctrine is not available.
* fix(service definition): Add interface aliases for easy decoration.
* fix(`CachingObjectToObjectMetadataFactory`): Fix invalid cache key (issue #3).

## 0.8.0

* refactor(`Context`): Returns null instead of throwing exception if the
  member is not found.
* refactor(`Context`): Context is now `Traversable`.
* feat(`MapperOptions`): Add context object to provide mapping options.
* feat(`MainTransformer`): Manual garbage collector.
* feat(`ObjectToObjectTransformer`): Option to disable lazy loading.
* feat(`ObjectToObjectTransformer`): Option to disable target value reading.
* refactor(`MainTransformer`): Make manual GC interval a static variable.
* refactor(`ObjectToObjectMetadataFactory`): Remove `Context`.
* refactor(`MapperOptions`): Simplify option names.
* fix: Fix deprecations.
* refactor(`ProxyGeneratorInterface`): Use class as input. Remove dependency on `ObjectToObjectMetadata`.
* refactor(`ProxyGeneratorInterface`): Move proxy namespace to top-level namespace.
* fix(`ProxyGenerator`): Prevent Doctrine entities from being proxied.

## 0.7.3

* test: Add tests for read-only targets.
* feat(`ObjectToObjectMetadata`): Store the reason if the object cannot use
  proxy.
* style(`Profiler`): Improve layout.
* feat(`Profiler`): Collect object to object metadata.
* test: In 8.2, read only classes cannot be lazy.
* fix(`ObjectToObjectTransformer`): If target is lazy and its constructor
  contains an eager argument, then the rest of the arguments must be eager.
* fix(`ObjectToObjectMetadataFactory`): If the target is not writable, skip the
  mapping.
* feat(`Profiler`): Show mapping table.
* refactor(`MappingFactory`): Separate cache warmer from
  `WarmableMappingFactory`.
* feat(`DoctrineEagerPropertiesResolver`): Automatically identifies ID columns
  of a Doctrine entity.

## 0.7.2

* style(`Profiler`): Cosmetic.
* refactor(`EagerPropertiesResolver`): Create `ChainEagerPropertiesResolver`.
* refactor(`HeuristicsEagerPropertiesResolver`): Genericize.
* perf(`ProxyRegistry`): Improve proxy generation performance.
* style(`ObjectToObjectTransformer`): Add comments for clarity.
* dx(`DebugPass`): Remove property info caching on debug mode.
* fix(`ObjectToObjectTransformer`): Disregard existing target if it is read
  only.
* fix(`ReaderWriter`): Fix reading unset properties on a proxy.

## 0.7.1

* style: Sort `composer.json`
* fix(`ObjectToObjectMetadataFactory`): Resolve proxy classes to real classes.
* fix(`ProxyGenerator`): Target proxy classes now does not depend on source
  class name.
* fix(`ProxyGenerator`): Borrow `__CG__` marker for compatibility with
  third-party libraries.
* feat(`MapperDataCollector`): Add lazy badge for lazy results.
* test: Fix psalm errors.
* fix: Mixed & untyped property was not previously working.
* feat(`DataCollector`): Add target type hint information.
* fix(`ObjectToObjectMetadataFactory`): Fix scalar type logic.
* test: Fix psalm errors.
* fix(`MainTransformer`): Inject kernel.debug
* revert(`Profiler`): Remove guessed badge because of the possibility of
  confusion.
* perf(`ObjectToObjectTransformer`): If source is null & target accepts null,
  we set null on the target directly.

## 0.7.0

* refactor(`ObjectToObjectTransformer`): Refactor for future extension.
* feat: Target object can now be a lazy-loading proxy.
* fix: Bump lower bound of `symfony/var-exporter` to `6.4.1` & `7.0.1`.
* test: Add mandatory config for `symfony/framework-bundle`.
* fix(`ProxyGenerator`): Handle readonly targets.
* build: Require `symfony/console`.
* test: Lazy loading id property from parent class.

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
