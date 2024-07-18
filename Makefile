PHP=php
COMPOSER=composer

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

.PHONY: phpunit
phpunit:
	$(eval c ?=)
	rm -rf var
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
