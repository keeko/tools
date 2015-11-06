# Keeko CLI Tools

[![Build Status](https://travis-ci.org/keeko/tools.svg?branch=master)](https://travis-ci.org/keeko/tools)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/keeko/tools/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/keeko/tools)
[![Code Coverage](https://scrutinizer-ci.com/g/keeko/tools/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/keeko/tools)

Keeko command line tools.

## Installation

Installation is done via composer:

```
$ composer global require 'keeko/tools=dev-master'
```

Make also sure `~/.composer/vendor/bin` is in your path. To keep the tools up-to-date, run `composer global update`.

## Usage

The general usage pattern is: `keeko <command>`. See `keeko list` to get all available commands and `keeko help <command>` to get the options and arguments.

Most commonly, you will want to run `keeko magic`. Which updates your composer.json with the required information regarding your package-type (only `keeko-module` is supported, yet). It automatically guesses all required information and generates module, action and response classes plus the API for the composer.json.
