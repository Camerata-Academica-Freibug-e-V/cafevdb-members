# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
#
# Thin wrapper around the shared Makefile of the build-system submodule,
# only the app-specific settings and rules live here.

SRCDIR = .
ABSSRCDIR = $(CURDIR)
DEV_LIB_DIR = $(ABSSRCDIR)/dev-scripts/lib

MAKEFILE_DEP = Makefile

APP_DEV_SETUP_DEPS = ts-app-config
APP_MOSTLYCLEAN_FILES = wrapper-build-hash
TIDE_ENTRY = $(SRCDIR)/src/main.ts

# The shared defaults name "composer" and "config" which this app does not
# ship, so the list is given explicitly. templates/ is added by the
# optional files of the shared Makefile.
APPSTORE_FILES =\
 appinfo\
 css\
 js\
 img\
 l10n\
 lib\
 vendor\
 vendor-wrapped\
 CHANGELOG.md\
 COPYING\
 README.md

# the Vue components of the app are bundled from the sources of the
# node module, too
JS_FILES_EXTRA = $(shell if [ -d "$(ABSSRCDIR)/node_modules/@rotdrop/nextcloud-vue-components" ]; then find $(ABSSRCDIR)/node_modules/@rotdrop/nextcloud-vue-components \( -name "*.js" -o -name "*.vue" -o -name "*.ts" \); fi)

ORM_CLI = $(PHP) $(SRCDIR)/dev-scripts/orm-cmd.php

include $(DEV_LIB_DIR)/makefile/app.mk

include $(DEV_LIB_DIR)/makefile/phpunit.mk
include $(DEV_LIB_DIR)/makefile/phpdoc.mk

toolkit-l10n: $(SCOPED_VENDOR_DIR)/autoload.php
vue-components-l10n: node_modules

###############################################################################
#
# PHPUnit

#@private
vendor-bin/phpunit/composer.lock: vendor-bin/phpunit/composer.json
	$(COMPOSER) bin phpunit update

$(PHING) $(PHPUNIT): composer.lock vendor-bin/phpunit/composer.lock
	$(COMPOSER) bin phpunit install

#@@ Runs the PHP tests which need a Nextcloud server (database and app container)
phpunit-server: $(PHPUNIT_DEPS)
	$(PHP) $(PHPCOVERAGE) $(PHPUNIT) -c phpunit.server.xml --no-coverage --display-all-issues
.PHONY: phpunit-server

# The phpunitfilter target comes from phpunit.mk. Use
# PHPUNIT_CONFIG=phpunit.server.xml for the tests needing a server.

#@private
orm-clear-cache: $(ABSSRCDIR)/vendor
	$(ORM_CLI) orm:clear-cache:metadata
.PHONY: orm-clear-cache

.PHONY: verifydb
verifydb: $(ABSSRCDIR)/vendor orm-clear-cache
	$(ORM_CLI) orm:validate-schema

###############################################################################
#
# START DOCS

# The default of phpdoc -Playout=smetana sometimes errors out, here it
# seems to work ...
PHPDOC_PLANTUML_ARGUMENTS = -Playout=smetana
# The plantuml default layout engine dot takes a huge amount of time,
# more than 2 hours ...
PHPDOC_PLANTUML_TIMEOUT_SECONDS = 120
# The shared default is to disable graphs, but they work here.
PHPDOC_GRAPHS = true
PHPDOC_PREREQ_TARGET = composer

#@@ Build the documentation. May take a long time
doc: phpdoc # doxygen jsdoc
.PHONY: doc
