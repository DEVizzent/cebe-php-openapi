TESTCASE=
XDEBUG=0
PHPARGS=-dmemory_limit=512M
XPHPARGS=
ifeq ($(XDEBUG),1)
XPHPARGS=-dzend_extension=xdebug.so -dxdebug.mode=debug -dxdebug.start_with_request=yes
endif

# Run make with IN_DOCKER=1 to run php commands in a docker container
DOCKER_PHP=
IN_DOCKER=0
PHP_CS_FIXER_VERSION=v3.95.28
PHP_CS_FIXER_SHA256=44dd8aa9b83163b4913bc39d3e26ba94221ffa03957d000e9aec252f656b0f60
ifeq ($(IN_DOCKER),1)
DOCKER_PHP=docker compose run --rm php
endif

all:
	@echo "the following commands are available:"
	@echo ""
	@echo "make check-style              # check code style"
	@echo "make fix-style                # fix code style"
	@echo "make install                  # install dependencies"
	@echo "make test                     # run PHPUnit tests"
	@echo "make lint                     # check validity of test data"
	@echo "make stan                     # check code with PHPStan"
	@echo ""
	@echo "You may add the IN_DOCKER parameter to run a command inside of docker container and not directly."
	@echo "make IN_DOCKER=1 ..."


check-style: php-cs-fixer.phar
	PHP_CS_FIXER_IGNORE_ENV=1 ./php-cs-fixer.phar fix src/ --diff --dry-run

fix-style: php-cs-fixer.phar
	$(DOCKER_PHP) vendor/bin/indent --tabs composer.json
	$(DOCKER_PHP) vendor/bin/indent --spaces .php-cs-fixer.dist.php
	$(DOCKER_PHP) ./php-cs-fixer.phar fix src/ --diff

install: composer.lock
	$(DOCKER_PHP) composer install --prefer-dist --no-interaction --no-progress --ansi

test: unit test-recursion.json test-recursion2.yaml test-recursion3_index.yaml test-empty-maps.json test-const.json

unit:
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) vendor/bin/phpunit --colors=always $(TESTCASE)

test-debug: unit-debug test-recursion.json test-recursion2.yaml test-recursion3_index.yaml test-empty-maps.json test-const.json

unit-debug:
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) vendor/bin/phpunit --debug --testdox --colors=always $(TESTCASE)

# test specific JSON files in tests/spec/data/
# e.g. test-recursion will run validation on tests/spec/data/recursion.json
test-%: tests/spec/data/%
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate $<

lint: install
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate tests/spec/data/reference/playlist.json
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate tests/spec/data/recursion.json
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate tests/spec/data/recursion2.yaml
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate tests/spec/data/empty-maps.json
	$(DOCKER_PHP) php $(PHPARGS) $(XPHPARGS) bin/php-openapi validate tests/spec/data/const.json

stan:
	$(DOCKER_PHP) php $(PHPARGS) vendor/bin/phpstan analyse -l 5 src

php-cs-fixer.phar:
	wget -q https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/$(PHP_CS_FIXER_VERSION)/php-cs-fixer.phar -O $@.tmp
	echo "$(PHP_CS_FIXER_SHA256)  $@.tmp" | sha256sum -c -
	mv $@.tmp $@
	chmod +x $@

# find spec classes that are not mentioned in tests with #[CoversClass] yet
coverage: .php-openapi-covA .php-openapi-covB
	diff $^
.INTERMEDIATE: .php-openapi-covA .php-openapi-covB
.php-openapi-covA:
	grep -rhoE 'CoversClass\([^)]+::class' tests | sed -E 's/.*[\]([A-Za-z]+)::class/\1/' | sort > $@
.php-openapi-covB:
	grep -rhPo '^class \w+' src/spec/ | awk '{print $$2}' |grep -v '^Type$$' | sort > $@

.PHONY: all check-style fix-style install test lint coverage

