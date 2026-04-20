<?php

/**
 * PHPUnit bootstrap for wp-proud-document.
 *
 * Load order:
 *   1. Patchwork — must be active before any file that defines functions you
 *      want to patch per-test, so its stream wrapper is in place when
 *      stubs.php and the plugin file are loaded.
 *   2. Composer autoload — loads Brain\Monkey and PHPUnit infrastructure.
 *   3. stubs.php — defines ProudPlugin stub and minimal WP function stubs.
 *      ProudPlugin is defined here so the plugin's class_exists() guard
 *      prevents the wp-proud-core require from running.
 *   4. Plugin file — included once; all tests run against these already-
 *      loaded class definitions.
 *
 * Run tests from the plugin root:
 *   composer install
 *   composer test
 */

require_once __DIR__ . '/../vendor/antecedent/patchwork/Patchwork.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs.php';

require_once __DIR__ . '/../wp-proud-document.php';
