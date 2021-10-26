SHELL := /bin/sh

tests: export APP_ENV=test
tests:
	composer install -n
	bin/console app:setup
	vendor/bin/phpunit $@ --testdox
.PHONY: tests
