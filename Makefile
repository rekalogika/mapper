PHP=php
SYMFONY=symfony
COMPOSER=composer
export APP_ENV=test

-include .env.local

.PHONY: test
test: dump phpstan psalm phpunit

.PHONY: dump
dump:
	$(COMPOSER) dump-autoload --optimize

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse

.PHONY: psalm
psalm:
	$(PHP) vendor/bin/psalm

.PHONY: clean
clean:
	rm -rf tests/var

.PHONY: phpunit
phpunit: clean
	$(eval c ?=)
	$(PHP) vendor/bin/phpunit $(c)

.PHONY: php-cs-fixer
php-cs-fixer: tools/php-cs-fixer
	$(PHP) $< fix --config=.php-cs-fixer.dist.php --verbose --allow-risky=yes

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
