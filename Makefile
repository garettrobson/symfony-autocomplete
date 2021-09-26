# Boilerplate
SHELL := bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c
.DELETE_ON_ERROR:
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

ifeq ($(origin .RECIPEPREFIX), undefined)
  $(error This Make does not support .RECIPEPREFIX. Please use GNU Make 4.0 or later)
endif
.RECIPEPREFIX = >
ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

# Check for box
ifeq (, $(shell which box))
$(error "No box command in $(PATH), please see https://github.com/box-project/box and ensure box is available in the environment PATH")
endif

# Commands
check:
> @echo "✅ box is available, using `which box`"
compile:
> @box compile
clean:
> @rm build -rf
recompile:clean compile
uninstall:
> @sudo rm /usr/local/bin/symfony-completer -f
install-bin: uninstall
> @sudo cp ./build/symfony-completer /usr/local/bin/symfony-completer
install-link: uninstall
> @sudo ln -s ${ROOT_DIR}/build/symfony-completer /usr/local/bin/symfony-completer
