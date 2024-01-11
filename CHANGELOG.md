# CHANGELOG

## 0.5.4

* Add a caching layer for `TypeResolver`
* `TraversableToTraversableTransformer` now accepts `Generator` as a target type

## 0.5.3

* Use `MappingFactoryInterface` everywhere instead of `MappingFactory`
* Move some `TypeUtil` methods to `TypeResolver` for optimization opportunities
* use `ObjectCacheFactory` to generate `ObjectCache` instances
* Update `MapperFactory` to reflect framework usage
* Use property info caching in non-framework usage
* Add mapping caching
* Change console command to use `rekalogika` prefix

## 0.5.2

* Support Symfony 7
* Fix service definition for `MapperFactory`

## 0.5.0

* Initial release
