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

PHING = $(ABSSRCDIR)/vendor-bin/phing/vendor/bin/phing
# PHPCOVERAGE = -d extension=pcov.so -d pcov.directory=$(ABSSRCDIR)/lib
PHPCOVERAGE = -d zend_extension=xdebug.so -d xdebug.mode=coverage
ORM_CLI = $(PHP) $(SRCDIR)/dev-scripts/orm-cmd.php

include $(DEV_LIB_DIR)/makefile/app.mk

DOC_BUILD_DIR = $(ABSBUILDDIR)/artifacts/doc

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

PHPUNIT_OUTPUT = $(BUILDDIR)/artifacts/tests/phpunit
PHPUNIT_JUNIT_LOG = $(PHPUNIT_OUTPUT)/junit-log.xml
PHPUNIT_JUNIT_LOG_HTML = $(PHPUNIT_OUTPUT)/junit-log
PHING_BUILD_XML = $(PHPUNIT_OUTPUT)/phing-build.xml

#@@ Runs the self-contained PHP tests, no Nextcloud server needed
phpunit: dophpunit $(PHPUNIT_JUNIT_LOG_HTML)/index.html
.PHONY: phpunit

#@@ Post-process junit-log xml file
phpjunit-log: $(PHPUNIT_JUNIT_LOG_HTML)/index.html
.PHONY: phpjunit-log

PHPUNIT_DEPS = $(PHPUNIT) $(SCOPED_VENDOR_DIR)/autoload.php $(TS_TYPES_AUTOLOAD)

#@private
dophpunit: $(PHPUNIT_DEPS)
	$(PHP) $(PHPCOVERAGE) $(PHPUNIT)\
 -c phpunit.xml\
 --coverage-html $(PHPUNIT_OUTPUT)/code-coverage\
 --log-junit $(PHPUNIT_JUNIT_LOG)\
 --display-all-issues
.PHONY: dophpunit

#@@ Runs the PHP tests which need a Nextcloud server (database and app container)
phpunit-server: $(PHPUNIT_DEPS)
	$(PHP) $(PHPCOVERAGE) $(PHPUNIT) -c phpunit.server.xml --no-coverage --display-all-issues
.PHONY: phpunit-server

$(PHPUNIT_JUNIT_LOG): # dophpunit

$(PHING_BUILD_XML): $(SRCDIR)/vendor-bin/phing/phing-build.xml.in $(MAKEFILE_DEP)
	mkdir -p $(dir $@)
	sed -e 's|%BASEDIR%|$(ABSSRCDIR)|g' -e 's|%INFILE%|$(PHPUNIT_JUNIT_LOG)|g' -e 's|%OUTPUTDIR%|$(PHPUNIT_OUTPUT)/junit-log|g' < $< > $@

$(PHPUNIT_JUNIT_LOG_HTML)/index.html: $(PHING_BUILD_XML) $(PHPUNIT_JUNIT_LOG) $(PHING)
	mkdir -p $(PHPUNIT_JUNIT_LOG_HTML)
	$(PHING) -f $(PHING_BUILD_XML)

PHPUNIT_CONFIG = phpunit.xml

#@@ Runs phpunit with a filter given by the PHPUNITTEST variable which should be specified on the command line. Use PHPUNIT_CONFIG=phpunit.server.xml for the tests needing a server.
phpunitfilter: $(PHPUNIT_DEPS)
	@if [ -z "$(PHPUNITTEST)" ]; then echo "Please add PHPUNITTEST=FILTER_EXPRESSION to the make command line" 1>&2; exit 1; fi
	$(PHP) $(PHPCOVERAGE) $(PHPUNIT) -c $(PHPUNIT_CONFIG) --no-coverage --display-all-issues --filter "$(PHPUNITTEST)"
.PHONY: phpunitfilter

.PHONY: verifydb
verifydb: $(ABSSRCDIR)/vendor
	$(ORM_CLI) orm:validate-schema

###############################################################################
#
# START DOCS

PHPDOC = /opt/phpDocumentor/bin/phpdoc
export PHPDOC_PLANTUML_BIN = $(shell which plantuml 2> /dev/null)
# The default of phpdoc -Playout=smetana sometimes errors out, here it
# seems to work ...
export PHPDOC_PLANTUML_ARGUMENTS = -Playout=smetana
# The plantuml default layout engine dot takes a huge amount of time,
# more than 2 hours ...
export PHPDOC_PLANTUML_TIMEOUTS_SECONDS = 120
# ... we therefore disable graphs by default.
PHPDOC_GRAPHS ?= true
PHPDOC_TEMPLATE =

#@@ Build the documentation. May take a long time
doc: phpdoc # doxygen jsdoc
.PHONY: doc

PHPDOC_HTML = $(DOC_BUILD_DIR)/phpdoc/

#@@ Run phpDocumentor
phpdoc: $(PHPDOC_HTML)/index.html
.PHONY: phpdoc

#@private
$(PHPDOC_HTML)/index.html: $(APP_BUILD_HASH) $(MAKEFILE_DEP)
	$(MAKE) composer
	rm -rf $(PHPDOC_HTML)
	mkdir -p $(PHPDOC_HTML)
	$(PHPDOC) run \
 $(PHPDOC_TEMPLATE) \
 -d $(SRCDIR)/lib \
 -d $(SRCDIR)/tests/phpunit \
 --defaultpackagename $(APP_NAME) \
 --force \
 --parseprivate \
 --visibility api,public,protected,private,internal \
 --sourcecode \
 --setting graphs.enabled=$(PHPDOC_GRAPHS) \
 --cache-folder $(ABSBUILDDIR)/phpdoc/cache \
 -t $(PHPDOC_HTML)
