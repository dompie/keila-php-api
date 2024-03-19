PHPUNIT			= ./bin/phpunit
COMPOSER		= composer

.PHONY: test
test:
	$(PHPUNIT) tests/

.PHONY: test-coverage
test-coverage:
	$(PHPUNIT) --no-progress --coverage-html tests-coverage/ tests/

.PHONY: help
help:
	@echo "test			Run all tests"
	@echo "test-coverage		Dump test coverage to test-coverage.txt"

.DEFAULT_GOAL := help
