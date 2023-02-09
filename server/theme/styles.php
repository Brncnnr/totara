<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is responsible for serving the one huge CSS of each theme.
 *
 * @package   core
 * @copyright 2009 Petr Skoda (skodak)  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

define('ABORT_AFTER_CONFIG', true);
require('../config.php');
require_once($CFG->dirroot.'/lib/csslib.php');

if ($slashargument = min_get_slash_argument()) {
    $slashargument = ltrim($slashargument, '/');
    if (substr_count($slashargument, '/') < 2) {
        css_send_css_not_found();
    }

    if (strpos($slashargument, '_s/') === 0) {
        // Can't use SVG.
        $slashargument = substr($slashargument, 3);
        $usesvg = false;
    } else {
        $usesvg = true;
    }

    // Totara: Removed chunking support as it's not used by currently supported browsers
    // Totara: Rewrote slash argument handling to be more robust
    // Totara: themename/rev/type/tenant(/rtl)?(/legacy)?
    $slashargument_parts = explode('/', $slashargument);
    $themename = min_clean_param(array_shift($slashargument_parts), 'SAFEDIR');
    $rev = min_clean_param(array_shift($slashargument_parts), 'INT');
    $type = min_clean_param(array_shift($slashargument_parts), 'SAFEDIR');
    $tenant = min_clean_param(array_shift($slashargument_parts), 'SAFEDIR');
    $rtl = in_array('rtl', $slashargument_parts);
    $legacy = in_array('legacy', $slashargument_parts);

    // Totara: add tenant.
    $tenant = (preg_match('#^tenant_(\d+)$#', $tenant, $matches)) ? (int)$matches[1] : 0;
} else {
    $themename = min_optional_param('theme', 'standard', 'SAFEDIR');
    $rev       = min_optional_param('rev', 0, 'INT');
    $type      = min_optional_param('type', 'all', 'SAFEDIR');
    $usesvg    = (bool)min_optional_param('svg', '1', 'INT');
    $rtl       = (bool)min_optional_param('rtl', 0, 'INT');
    $legacy    = (bool)min_optional_param('legacy', 0, 'INT');

    // Totara: add tenant.
    $tenant    = min_optional_param('tenant', 'tenant_0', PARAM_SAFEDIR);
    $tenant = (preg_match('#^tenant_(\d+)$#', $tenant, $matches)) ? (int)$matches[1] : 0;
}

// Totara: Removed chunking support as it's not used by currently supported browsers
if (!in_array($type, ['all', 'all-rtl', 'editor'])) {
    css_send_css_not_found();
}

if (file_exists("$CFG->dirroot/theme/$themename/config.php")) {
    // The theme exists in standard location - ok.
} else if (!empty($CFG->themedir) and file_exists("$CFG->themedir/$themename/config.php")) {
    // Alternative theme location contains this theme - ok.
} else {
    header('HTTP/1.0 404 not found');
    die('Theme was not found, sorry.');
}

$candidatedir = "$CFG->localcachedir/theme/{$rev}/{$themename}/tenant{$tenant}/css";
$etag = "{$rev}/{$themename}/{$type}/{$tenant}";
$candidatename = $type;
if (!$usesvg) {
    // Add to the sheet name, one day we'll be able to just drop this.
    $candidatedir .= '/nosvg';
    $etag .= '/nosvg';
}

// Totara: Removed chunking support as it's not used by currently supported browsers

// Totara RTL support.
if ($rtl) {
    $candidatename .= '-rtl';
    $etag .= '/rtl';
}
if ($legacy) {
    $candidatename .= '-legacy';
    $etag .= '/legacy';
}

$candidatesheet = "$candidatedir/$candidatename.css";

$etag = sha1($etag);

if (file_exists($candidatesheet)) {
    if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) || !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        // We do not actually need to verify the etag value because our files
        // never change in cache because we increment the rev counter.
        css_send_unmodified(filemtime($candidatesheet), $etag);
    }
    css_send_cached_css($candidatesheet, $etag);
}

// Ok, now we need to start normal moodle script, we need to load all libs and $DB.
define('ABORT_AFTER_CONFIG_CANCEL', true);

define('NO_MOODLE_COOKIES', true); // Session not used here.
define('NO_UPGRADE_CHECK', true);  // Ignore upgrade check.

require("$CFG->dirroot/lib/setup.php");

$theme = theme_config::load($themename);
$theme->force_svg_use($usesvg);
$theme->set_rtl_mode($type === 'all-rtl' ? true : false);
$theme->set_legacy_browser($legacy);

$themerev = theme_get_revision();

$cache = true;
if ($themerev <= 0 or $themerev != $rev) {
    $rev = $themerev;
    $cache = false;

    $candidatedir = "$CFG->localcachedir/theme/$rev/$themename/{$tenant}/css";
    $etag = "$rev/$themename/$type/{$tenant}";
    $candidatename = $type;
    if (!$usesvg) {
        // Add to the sheet name, one day we'll be able to just drop this.
        $candidatedir .= '/nosvg';
        $etag .= '/nosvg';
    }

    // Totara RTL support.
    if ($rtl) {
        $candidatename .= '-rtl';
        $etag .= '/rtl';
    }
    if ($legacy) {
        $candidatename .= '-legacy';
        $etag .= '/legacy';
    }

    $candidatesheet = "$candidatedir/$candidatename.css";
    $etag = sha1($etag);
}

make_localcache_directory('theme', false);

if ($type === 'editor') {
    $csscontent = $theme->get_css_content_editor();
    css_store_css($theme, "$candidatedir/editor.css", $csscontent);

} else {
    // Fetch a lock whilst the CSS is fetched as this can be slow and CPU intensive.
    // Each client should wait for one to finish the compilation before starting the compiler.
    $lockfactory = \core\lock\lock_config::get_lock_factory('core_theme_get_css_content');
    $lock = $lockfactory->get_lock($themename, rand(90, 120));

    if (file_exists($candidatesheet)) {
        // The file was built while we waited for the lock, we release the lock and serve the file.
        if ($lock) {
            $lock->release();
        }

        if ($cache) {
            css_send_cached_css($candidatesheet, $etag);
        } else {
            css_send_uncached_css(file_get_contents($candidatesheet));
        }
    }

    $csscontent = $theme->get_css_content_by($type, $tenant);

    // Totara: Removed chunking support as it's not used by currently supported browsers

    css_store_css($theme, "$candidatedir/$candidatename.css", $csscontent);

    if ($lock) {
        // Now that the CSS has been generated and/or stored, release the lock.
        // This will allow waiting clients to use the newly generated and stored CSS.
        $lock->release();
    }
}

if (!$cache) {
    // Do not pollute browser caches if invalid revision requested,
    // let's ignore legacy IE breakage here too.
    css_send_uncached_css($csscontent);

} else {
    // Real browsers - this is the expected result!
    css_send_cached_css_content($csscontent, $etag);
}
