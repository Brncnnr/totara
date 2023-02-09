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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

use totara_tui\local\locator\bundle;
use totara_tui\local\mediation\helper;
use totara_tui\local\mediation\styles\mediator;
use totara_tui\local\mediation\styles\resolver;

// Disable debug messages and any errors in output, comment out when debugging or look into error log!
define('NO_DEBUG_DISPLAY', true);
// We need just the values from config.php and minlib.php if we have the CSS cached already.
define('ABORT_AFTER_CONFIG', true);
// We embed time resolution information in the headers.
define('TUI_RESOLUTION_START', microtime(true));

require('../../config.php'); // this stops immediately at the beginning of lib/setup.php

// Required libraries.
require_once($CFG->dirroot . '/lib/configonlylib.php');
// Theses need to be automatically required as we support early resolving.
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/helper.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/resolver.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/styles/resolver.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/mediator.php');
require_once($CFG->dirroot . '/totara/tui/classes/local/mediation/styles/mediator.php');

[$themename, $rev, $suffix, $option_rtl, $component, $tenant] = helper::get_args([
    'theme' => 'SAFEDIR',
    'rev' => 'INT',
    'suffix' => 'SAFEDIR',
    'direction' => 'SAFEDIR',
    'component' => 'SAFEDIR',
    'tenant' => 'SAFEDIR',
]);
$option_rtl = ($option_rtl === 'rtl');
$tenant = (preg_match('#^tenant_(\d+)$#', $tenant, $matches)) ? (int)$matches[1] : 0;
helper::validate_theme_name($themename) || mediator::send_not_found();

if ($rev !== -1) {
    (new resolver(mediator::class, $rev, $themename, $component, $suffix, $tenant, $option_rtl))->resolve();
}

// Ok, now we need to start normal moodle script, we need to load all libs and $DB.
define('ABORT_AFTER_CONFIG_CANCEL', true);
define('NO_MOODLE_COOKIES', true); // Session not used here.
define('NO_UPGRADE_CHECK', true);  // Ignore upgrade check.
require("$CFG->dirroot/lib/setup.php");

// OK, don't trust suffix, or rev
// Suffix is in the URL only for proxies and web caches, and so that we can optimally hit our own cache.
$rev = bundle::get_css_rev();
$suffix = bundle::get_css_suffix_for_url();
try {
    (new resolver(mediator::class, $rev, $themename, $component, $suffix, $tenant, $option_rtl))->resolve();
} catch (Throwable $e) {
    // support "report" parameter used to show errors in the UI in development mode.
    if (min_optional_param('report', null, 'RAW') === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache');
        echo json_encode([
            'status' => 'error',
            'stack' => $CFG->debugdeveloper
                ? get_class($e) . ': ' . $e->getMessage() .
                    ($e instanceof moodle_exception && isset($e->debuginfo) ? " ({$e->debuginfo})" : '') . "\n" .
                    $e->getTraceAsString()
                : null,
        ]);
    } else {
        throw $e;
    }
}