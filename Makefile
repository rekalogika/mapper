include .env
-include .env.local

export APP_ENV

PHP := $(shell echo $(PHP))
COMPOSER := $(shell echo $(COMPOSER))

.PHONY: test
test: dump phpstan psalm phpunit

.PHONY: dump
dump:
	$(COMPOSER) dump-autoload --optimize

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse

.PHONY: phpstan-baseline
phpstan-baseline:
	$(PHP) vendor/bin/phpstan analyse --generate-baseline

.PHONY: psalm
psalm:
	$(PHP) vendor/bin/psalm --no-cache

.PHONY: psalm-baseline
psalm-baseline:
	$(PHP) vendor/bin/psalm --no-cache --update-baseline

.PHONY: clean
clean:
	rm -rf tests/var

.PHONY: warmup
warmup:
	$(PHP) tests/bin/console cache:warmup --env=test

.PHONY: phpunit
phpunit: clean warmup
	$(eval c ?=)
	$(PHP) vendor/bin/phpunit $(c)

.PHONY: php-cs-fixer
php-cs-fixer: tools/php-cs-fixer
	PHP_CS_FIXER_IGNORE_ENV=1 $(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

.PHONY: tools/php-cs-fixer
tools/php-cs-fixer:
	phive install php-cs-fixer

.PHONY: rector
rector:
	$(PHP) vendor/bin/rector process > rector.log
	make php-cs-fixer

.PHONY: serve
serve:
	$(PHP) tests/bin/console cache:clear
	$(PHP) tests/bin/console asset:install tests/public/
	cd tests && sh -c "APP_ENV=test $(SYMFONY) server:start --document-root=public"
