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
 * This file contains various javascript related functions,
 * all functions here are self contained and can be used in ABORT_AFTER_CONFIG scripts.
 *
 * @package   core_lib
 * @copyright 2012 Petr Skoda (skodak)  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Send javascript file content with as much caching as possible
 * @param string $jspath
 * @param string $etag
 * @param string $filename
 */
function js_send_cached($jspath, $etag, $filename = 'javascript.php') {
    require(__DIR__ . '/xsendfilelib.php');

    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;

    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="'.$filename.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($jspath)) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', immutable');
    header('Accept-Ranges: none');
    header('Content-Type: application/javascript; charset=utf-8');

    if (xsendfile($jspath)) {
        die;
    }

    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.filesize($jspath));
    }

    readfile($jspath);
    die;
}

/**
 * Send javascript without any caching
 * @param string $js
 * @param string $filename
 */
function js_send_uncached($js, $filename = 'javascript.php') {
    header('Content-Disposition: inline; filename="'.$filename.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + 2) .' GMT');
    header('Pragma: ');
    header('Accept-Ranges: none');
    header('Content-Type: application/javascript; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.strlen($js));
    }

    echo $js;
    die;
}

/**
 * Send file not modified headers
 * @param int $lastmodified
 * @param string $etag
 */
function js_send_unmodified($lastmodified, $etag) {
    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;
    header('HTTP/1.1 304 Not Modified');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Cache-Control: public, max-age='.$lifetime);
    header('Content-Type: application/javascript; charset=utf-8');
    header('Etag: "'.$etag.'"');
    if ($lastmodified) {
        header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');
    }
    die;
}

/**
 * Create cache file for JS content
 * @param string $file full file path to cache file
 * @param string $content JS code
 */
function js_write_cache_file_content($file, $content) {
    global $CFG;

    clearstatcache();
    if (!file_exists(dirname($file))) {
        @mkdir(dirname($file), $CFG->directorypermissions, true);
    }

    // Prevent serving of incomplete file from concurrent request,
    // the rename() should be more atomic than fwrite().
    ignore_user_abort(true);
    if ($fp = fopen($file.'.tmp', 'xb')) {
        fwrite($fp, $content);
        fclose($fp);
        rename($file.'.tmp', $file);
        @chmod($file, $CFG->filepermissions);
        @unlink($file.'.tmp'); // just in case anything fails
    }
    ignore_user_abort(false);
    if (connection_aborted()) {
        die;
    }
}

/**
 * Tell the browser that the JSON file they have in the cache is unmodified.
 *
 * @param int $lastmodified
 * @param string $etag
 */
function json_send_unmodified(int $lastmodified, string $etag) {
    // 7 days only, you should use etags and if-none-modified to serve stale caches to optimise longer.
    $lifetime = 60 * 60 * 24 * 7;

    header('HTTP/1.1 304 Not Modified');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Cache-Control: public, max-age='.$lifetime);
    header('Content-Type: application/json; charset=utf-8');
    header('Etag: "'.$etag.'"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', $lastmodified) .' GMT');

    die;
}

/**
 * Sends JSON directly without caching it.
 *
 * @param string $content
 * @param string $etag Optional etag to set when serving this uncached CSS.
 */
function json_send_uncached_content(string $content, string $etag) {
    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="json_debug.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + 10) .' GMT');
    header('Pragma: ');
    header('Accept-Ranges: none');
    header('Content-Type: application/json; charset=utf-8');

    echo $content;
    die;
}

/**
 * Sends the cached JSON file.
 *
 * @param string $absolute_path The path to the JSON file we want to serve.
 * @param string|null $etag Etag to set when serving this uncached CSS.
 */
function json_send_cached(string $absolute_path, string $etag) {
    // 7 days only, you should use etags and if-none-modified to serve stale caches to optimise longer.
    $lifetime = 60 * 60 * 24 * 7;

    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="json.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($absolute_path)) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', immutable');
    header('Accept-Ranges: none');
    header('Content-Type: application/json; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.filesize($absolute_path));
    }

    readfile($absolute_path);
    die;
}

/**
 * Sends cached JSON content.
 *
 * @param string $content The actual json to serve
 * @param string $etag The revision to make sure we utilise any caches.
 */
function json_send_cached_content(string $content, string $etag) {
    // 7 days only, you should use etags and if-none-modified to serve stale caches to optimise longer.
    $lifetime = 60 * 60 * 24 * 7;

    header('Etag: "'.$etag.'"');
    header('Content-Disposition: inline; filename="json.php"');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', time()) .' GMT');
    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age='.$lifetime.', immutable');
    header('Accept-Ranges: none');
    header('Content-Type: application/json; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: '.strlen($content));
    }

    echo $content;
    die;
}

/**
 * Create cache file for JS file in a path.
 * @param string $file full file path to cache file
 * @param string $absolute_path JS code
 */
function js_write_cache_file_from_path($file, $absolute_path) {
    global $CFG;

    clearstatcache();
    if (!file_exists(dirname($file))) {
        @mkdir(dirname($file), $CFG->directorypermissions, true);
    }

    if (strpos($absolute_path, '..') !== false) {
        // This should never happen, don't entertain it, just quit.
        debugging('Safety check: path traversal is not allowed', DEBUG_DEVELOPER);
        js_write_cache_file_content($file, 'Invalid file path provided for copying.');
        return;
    }
    if (substr($absolute_path, 0, strlen($CFG->srcroot)) !== $CFG->srcroot) {
        debugging('Safety check: attempted to cache file from outside of srcroot directory.', DEBUG_DEVELOPER);
        js_write_cache_file_content($file, 'Invalid file path provided for copying.');
        return;
    }

    $temp_file = $file . '-' . bin2hex(random_bytes(6)) . '.tmp';
    // Prevent serving of incomplete file from concurrent request,
    // Rename should be more atomic the copy if the file is large.
    copy($absolute_path, $temp_file);
    rename($temp_file, $file);

    // Tidy things up.
    @chmod($file, $CFG->filepermissions);
    @unlink($file.'.tmp'); // just in case anything fails

    ignore_user_abort(false);
    if (connection_aborted()) {
        die;
    }
}

/**
 * Sends a 404 message about CSS not being found.
 */
function js_send_css_not_found() {
    header('HTTP/1.0 404 not found');
    die('JS was not found, sorry.');
}
