.DEFAULT_GOAL := help

help:
	@echo ""
	@echo "Available tasks:"
	@echo "    test            Run all tests and generate coverage"
	@echo "    watch           Run all tests and coverage when a source file is upaded"
	@echo "    lint            Run only linter and code style checker"
	@echo "    unit            Run unit tests and generate coverage"
	@echo "    rector-dry-run  Run rector code migrations and show the proposed changes"
	@echo "    rector          Run rector code migrations and apply the proposed changes"
	@echo "    unit            Run static analysis"
	@echo "    vendor          Install dependencies"
	@echo "    clean           Remove vendor and composer.lock"
	@echo ""

vendor: $(wildcard composer.lock)
	composer install --prefer-dist

lint: vendor
	vendor/bin/phplint . --exclude=vendor/
	vendor/bin/ecs check src tests

unit: vendor
	phpdbg -qrr vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml --coverage-html=./report/

static: vendor
	vendor/bin/phpstan analyse src --level max

rector: vendor
	vendor/bin/rector process --dry-run

rector-fix: vendor
	vendor/bin/rector process
	vendor/bin/ecs check src tests --fix

watch: vendor
	find . -name "*.php" -not -path "./vendor/*" -o -name "*.json" -not -path "./vendor/*" | entr -c make test

test: lint unit static

clean:
	rm -rf vendor
	rm composer.lock
	rm .phplint-cache
	rm -rf report

.PHONY: help lint unit watch test clean rector rector-dry-run
