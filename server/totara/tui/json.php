<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

use totara_tui\local\locator\bundle;
use totara_tui\local\mediation\helper;
use totara_tui\local\mediation\json\mediator;
use totara_tui\local\mediation\json\resolver;

// Disable debug messages and any errors in output, comment out when debugging or look into error log!
define('NO_DEBUG_DISPLAY', true);
// We need just the values from config.php and minlib.php if we have the JS cached already.
define('ABORT_AFTER_CONFIG', true);

require('../../config.php'); // this stops immediately at the beginning of lib/setup.php

// Required libraries.
require_once($CFG->dirroot . '/lib/configonlylib.php');
// Theses need to be automatically required as we support early resolving.
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/helper.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/resolver.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/json/resolver.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/mediator.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/json/mediator.php');

[$rev, $component, $file] = helper::get_args([
    'rev' => 'INT',
    'bundle' => 'SAFEDIR',
    'file' => 'SAFEDIR',
]);

// Confirm the file is allowed, otherwise send not found (404)
resolver::validate_requested_file($file) || mediator::send_not_found();

if ($rev > 0 and $rev < (time() + 60*60)) {
    (new resolver(mediator::class, $rev, $component, $file))->resolve();
}

// Ok, now we need to start normal moodle script, we need to load all libs and $DB.
define('ABORT_AFTER_CONFIG_CANCEL', true);
define('NO_MOODLE_COOKIES', true); // Session not used here.
define('NO_UPGRADE_CHECK', true);  // Ignore upgrade check.
require("$CFG->dirroot/lib/setup.php");

// OK, don't trust suffix, or rev
// Suffix is in the URL only for proxies and web caches, and so that we can optimally hit our own cache.
$rev = bundle::get_js_rev();
$suffix = bundle::get_js_suffix_for_url();
(new resolver(mediator::class, $rev, $component, $file))->resolve();
