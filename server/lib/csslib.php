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
 * This file contains CSS file serving functions.
 *
 * NOTE: these functions are not expected to be used from any addons.
 *
 * @package core
 * @copyright 2012 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!defined('THEME_DESIGNER_CACHE_LIFETIME')) {
    // This can be also set in config.php file,
    // it needs to be higher than the time it takes to generate all CSS content.
    define('THEME_DESIGNER_CACHE_LIFETIME', 10);
}

/**
 * Stores CSS in a file at the given path.
 *
 * This function either succeeds or throws an exception.
 *
 * @param theme_config $theme The theme that the CSS belongs to.
 * @param string $csspath The path to store the CSS at.
 * @param string $csscontent the complete CSS in one string
 * @param bool $unused1 Deprecated. Formerly used for IE 9 support.
 * @param string $unused2 Deprecated. Formerly used for IE 9 support.
 */
function css_store_css(theme_config $theme, $csspath, $csscontent, $unused1 = false, $unused2 = null) {
    global $CFG;

    clearstatcache();
    if (!file_exists(dirname($csspath))) {
        @mkdir(dirname($csspath), $CFG->directorypermissions, true);
    }

    // Totara: Removed chunking support as it's not used by currently supported browsers

    // Prevent serving of incomplete file from concurrent request,
    // the rename() should be more atomic than fwrite().
    ignore_user_abort(true);

    // First up write out the single file for all those using decent browsers.
    css_write_file($csspath, $csscontent);

    // Totara: Removed support for chunking CSS

    ignore_user_abort(false);
    if (connection_aborted()) {
        die;
    }
}

/**
 * Writes a CSS file.
 *
 * @param string $filename
 * @param string $content
 */
function css_write_file($filename, $content) {
    global $CFG;
    if ($fp = fopen($filename.'.tmp', 'xb')) {
        fwrite($fp, $content);
        fclose($fp);
        rename($filename.'.tmp', $filename);
        @chmod($filename, $CFG->filepermissions);
        @unlink($filename.'.tmp'); // Just in case anything fails.
    }
}

/**
 * Deprecated. Formerly chunked CSS by max selectors, now is a no-op.
 *
 * @deprecated Since Totara 13.0
 * @param string $css The CSS to chunk.
 * @param string $unused1 Unused.
 * @param int $unused2 Unused.
 * @param int $unused3 Unused.
 * @return array An array containing a single CSS chunk.
 */
function css_chunk_by_selector_count($css, $unused1, $unused2 = 4095, $unused3 = 50) {
    debugging(
        "Chunking of CSS has been deprecated as it's not used by currently supported browsers. " .
        "Please serve the entire CSS content instead.",
        DEBUG_DEVELOPER
    );
    return [$css];
}

/**
 * Sends a cached CSS file
 *
 * This function sends the cached CSS file. Remember it is generated on the first
 * request, then optimised/minified, and finally cached for serving.
 *
 * @param string $csspath The path to the CSS file we want to serve.
 * @param string $etag The revision to make sure we utilise any caches.
 */
function css_send_cached_css($csspath, $etag) {
    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;

    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="styles.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($csspath)) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', immutable');
    header('Accept-Ranges: none');
    header('Content-Type: text/css; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.filesize($csspath));
    }

    readfile($csspath);
    die;
}

/**
 * Sends a cached CSS content
 *
 * @param string $csscontent The actual CSS markup.
 * @param string $etag The revision to make sure we utilise any caches.
 */
function css_send_cached_css_content($csscontent, $etag) {
    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;

    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="styles.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', immutable');
    header('Accept-Ranges: none');
    header('Content-Type: text/css; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.strlen($csscontent));
    }

    echo($csscontent);
    die;
}

/**
 * Sends CSS directly without caching it.
 *
 * This function takes a raw CSS string, optimises it if required, and then
 * serves it.
 * Turning both themedesignermode and CSS optimiser on at the same time is awful
 * for performance because of the optimiser running here. However it was done so
 * that theme designers could utilise the optimised output during development to
 * help them optimise their CSS... not that they should write lazy CSS.
 *
 * @param string $css
 * @param string|null $etag Optional etag to set when serving this uncached CSS.
 */
function css_send_uncached_css($css, $etag = null) {
    if ($etag !== null) {
        header('Etag: "'.$etag.'"');
    }
    header('Content-Disposition: inline; filename="styles_debug.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + THEME_DESIGNER_CACHE_LIFETIME) .' GMT');
    header('Pragma: ');
    header('Accept-Ranges: none');
    header('Content-Type: text/css; charset=utf-8');

    if (is_array($css)) {
        $css = implode("\n\n", $css);
    }
    echo $css;
    die;
}

/**
 * Send file not modified headers
 *
 * @param int $lastmodified
 * @param string $etag
 */
function css_send_unmodified($lastmodified, $etag) {
    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;
    header('HTTP/1.1 304 Not Modified');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Cache-Control: public, max-age='.$lifetime);
    header('Content-Type: text/css; charset=utf-8');
    header('Etag: "'.$etag.'"');
    if ($lastmodified) {
        header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');
    }
    die;
}

/**
 * Sends a 404 message about CSS not being found.
 */
function css_send_css_not_found() {
    header('HTTP/1.0 404 not found');
    die('CSS was not found, sorry.');
}