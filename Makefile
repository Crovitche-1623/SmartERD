SHELL := /bin/sh

tests: export APP_ENV=test
tests:
	composer install -n
	vendor/bin/phpunit $@
.PHONY: tests
