all: dev-setup build-js-production test

# Dev env management
dev-setup: clean clean-dev npm-init

npm-init:
	npm ci

npm-update:
	npm update

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

# Testing
test:
	npm run test

test-watch:
	npm run test:watch

test-coverage:
	npm run test:coverage

# Cleaning
clean:
	rm -f js/*

clean-dev:
	rm -rf node_modules

