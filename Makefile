.PHONY: codestyle
codestyle: ## Run codestyle
	./vendor/bin/ecs --no-progress-bar check . --config easy-coding-standard.php

.PHONY: fix-codestyle
fix-codestyle: ## Fix codestyle
	./vendor/bin/ecs --no-progress-bar check . --fix --config easy-coding-standard.php

.PHONY: deps-security-test
deps-security-test: ## Check security
	./vendor/bin/security-checker security:check

.PHONY: install
install: ## Install dependencies
	composer install

.PHONY: update
update: ## Update dependencies
	composer update

.PHONY: test
test: ## Execute tests
	./vendor/bin/phpunit --coverage-text --colors=never --bootstrap ./vendor/autoload.php tests --log-junit ../build/logs/junit.xml --coverage-clover ../build/logs/clover.xml
