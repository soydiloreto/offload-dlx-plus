# offload-plus — developer task runner.
#
# All PHP-based commands run inside the official `composer:2` Docker
# image by default. That keeps the host clean of plugin-specific PHP
# extensions (dom, mbstring, xml, xmlwriter, etc.) which the standard
# WSL `php-cli` build tends to lack. The Composer + dev-tooling
# versions still come from composer.lock either way, so the runtime
# difference vs CI is just the PHP-extension surface area.
#
# Usage:
#   make            # = make help
#   make install    # composer install
#   make check      # everything CI runs
#   make release    # check + version-alignment dry-run
#
# Override DOCKER=0 to invoke local binaries instead. Only viable on
# hosts that already have a full PHP CLI with the required extensions
# (dom, mbstring, xml, xmlwriter, libxml, openssl, json, fileinfo,
# tokenizer) and `wp` and `npx` available on $PATH.

SHELL := /bin/bash

# -- Docker plumbing ---------------------------------------------------
# Mount the project read/write at /app, run as the host user so
# composer doesn't leave root-owned files in vendor/.
DOCKER ?= 1
DOCKER_USER := $(shell id -u):$(shell id -g)
DOCKER_RUN  := docker run --rm -u $(DOCKER_USER) -v $(CURDIR):/app -w /app

# Pin floating tags via env override for reproducibility:
#   make stan COMPOSER_IMAGE=composer:2.7
COMPOSER_IMAGE ?= composer:2
WP_CLI_IMAGE   ?= wordpress:cli
PHP_IMAGE      ?= php:8.3-cli

# `--network host` lets the wordpress:cli container reach the wp-env
# MySQL on the same host. It is unsupported on Docker Desktop for
# macOS/Windows; on those hosts override DOCKER_NET= to drop it (and
# arrange WP-CLI / integration tests another way, e.g. via wp-env).
DOCKER_NET ?= --network host

ifeq ($(DOCKER),1)
COMPOSER  := $(DOCKER_RUN) $(COMPOSER_IMAGE) composer
VENDOR    := $(DOCKER_RUN) $(COMPOSER_IMAGE)
PSALM_CMD := $(DOCKER_RUN) $(PHP_IMAGE) ./vendor/bin/psalm
WP_CLI    := $(DOCKER_RUN) $(DOCKER_NET) $(WP_CLI_IMAGE)
INTEG     := $(DOCKER_RUN) $(DOCKER_NET) $(COMPOSER_IMAGE)
else
COMPOSER  := composer
VENDOR    :=
PSALM_CMD := ./vendor/bin/psalm
WP_CLI    := wp
INTEG     :=
endif

# -- Default target ----------------------------------------------------
.DEFAULT_GOAL := help

.PHONY: help
help: ## Show this help.
	@awk 'BEGIN {FS = ":.*##"; printf "\nTargets:\n"} \
	  /^[a-zA-Z0-9_-]+:.*##/ {printf "  \033[1;32m%-18s\033[0m %s\n", $$1, $$2}' \
	  $(MAKEFILE_LIST)
	@echo
	@echo "Override the Docker mode with DOCKER=0 to use local binaries."

# -- Setup -------------------------------------------------------------
.PHONY: install
install: ## Install dev dependencies (composer install).
	$(COMPOSER) install --no-interaction --prefer-dist --no-progress

.PHONY: update
update: ## Update dev dependencies (composer update).
	$(COMPOSER) update --no-interaction --prefer-dist --no-progress

# -- Linting / static analysis ----------------------------------------
.PHONY: lint
lint: ## PHPCS + WordPress Coding Standards.
	$(VENDOR) ./vendor/bin/phpcs

.PHONY: lint-fix
lint-fix: ## Auto-fix PHPCS violations where possible.
	$(VENDOR) ./vendor/bin/phpcbf

.PHONY: stan
stan: ## PHPStan level 8 (no baseline).
	$(VENDOR) ./vendor/bin/phpstan analyse --memory-limit=2G --no-progress

.PHONY: psalm
psalm: ## Psalm taint analysis (XSS / SQLi / RCE).
	$(PSALM_CMD) --taint-analysis --no-cache --no-progress

.PHONY: i18n
i18n: ## Generate offload-plus.pot via WP-CLI.
	mkdir -p build
	$(WP_CLI) i18n make-pot . build/offload-plus.pot \
	    --slug=offload-plus \
	    --domain=offload-plus \
	    --exclude=tests,vendor,node_modules,.wordpress-org,assets,docs,build

# -- Tests -------------------------------------------------------------
.PHONY: test
test: test-unit ## Run the unit-test suite (default — fast, no WP needed).

.PHONY: test-unit
test-unit: ## Run only the unit-test suite (no WordPress runtime).
	$(VENDOR) ./vendor/bin/phpunit --testsuite unit

.PHONY: test-integration
test-integration: ## Run integration tests against the wp-env stack (must be `make env` first).
	$(INTEG) ./vendor/bin/phpunit --testsuite integration

# -- Aggregate ---------------------------------------------------------
.PHONY: check
check: lint stan psalm test ## Run every quality gate CI runs (lint, stan, psalm, unit tests).
	@echo "✔ All checks passed."

# -- Local dev environment (wp-env) ------------------------------------
.PHONY: env env-up
env: env-up ## Alias of env-up.
env-up: ## Start the local wp-env Docker stack.
	npx wp-env start

.PHONY: env-down
env-down: ## Stop the local wp-env Docker stack.
	npx wp-env stop

.PHONY: env-clean
env-clean: ## Destroy the local wp-env Docker stack and its volumes.
	npx wp-env destroy

# -- Deploy / release --------------------------------------------------
# Override DEPLOY_SCRIPT= to point at your own copy of the script.
DEPLOY_SCRIPT ?= $(HOME)/.local/bin/offload-plus-deploy-to-mug

.PHONY: deploy-test
deploy-test: ## Deploy current branch to the mug-website-v2 sibling repo for manual smoke-testing.
	@if [ ! -x "$(DEPLOY_SCRIPT)" ]; then \
	  echo "deploy script not found at $(DEPLOY_SCRIPT)."; \
	  echo "Override DEPLOY_SCRIPT=/path/to/script to use a custom location."; \
	  exit 1; \
	fi
	"$(DEPLOY_SCRIPT)"

.PHONY: release
release: check ## Pre-release validation: full quality gate + version-alignment dry-run.
	@echo "── version alignment check ─────────────────────────────"
	@PHP_VERSION=$$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' offload-plus.php | head -1 | sed -E 's/.*Version:[[:space:]]*//'); \
	 STABLE_TAG=$$(grep -E '^Stable tag:' readme.txt | sed -E 's/Stable tag:[[:space:]]*//'); \
	 PHP_BASE=$$(echo $$PHP_VERSION | sed -E 's/-(dev|alpha|beta|rc).*$$//'); \
	 echo "  PHP header Version : $$PHP_VERSION"; \
	 echo "  PHP base (no -dev) : $$PHP_BASE"; \
	 echo "  readme Stable tag  : $$STABLE_TAG"; \
	 if [ "$$PHP_BASE" = "$$STABLE_TAG" ]; then \
	   echo "  → match ✔"; \
	 else \
	   echo "  → MISMATCH ✗ (PHP base must equal readme Stable tag at tag time)"; exit 1; \
	 fi
	@echo "✔ Ready to tag."

# -- Cleanup -----------------------------------------------------------
.PHONY: clean
clean: ## Remove caches, build artefacts, and temporary files.
	rm -rf build .phpunit.result.cache .phpunit.cache .phpcs-cache .phpstan .psalm
	@echo "✔ Cleaned."
