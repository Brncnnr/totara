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
 * @package totara_msteams
 * @author  Remote-Learner.net Inc
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2016 onwards Microsoft Open Technologies, Inc. (http://msopentech.com/)
 */

namespace totara_msteams;

defined('MOODLE_INTERNAL') || die;

use moodle_exception;
use totara_msteams\check\verifier;
use totara_msteams\exception\manifest_exception;
use totara_msteams\manifest\generator;
use totara_msteams\manifest\outputs\zip_output;

/**
 * Helper functions for manifest.
 */
final class manifest_helper {
    const GUID_NULL = '00000000-0000-0000-0000-000000000000';

    /**
     * Simple validation for GUID aka UUID.
     *
     * @param mixed $guid
     * @return boolean
     */
    public static function is_valid_guid($guid): bool {
        if (empty($guid)) {
            return false;
        }
        $guid = (string)$guid;
        if ($guid === self::GUID_NULL) {
            // GUID_NULL is not acceptable.
            return false;
        }
        // Ensure (8 hexdigits) - (4 hexdigits) - (4 hexdigits) - (4 hexdigits) - (12 hexdigits).
        $hd = '[0-9a-f]';
        return preg_match("/^{$hd}{8}-{$hd}{4}-{$hd}{4}-{$hd}{4}-{$hd}{12}$/i", $guid);
    }

    /**
     * Return the length of the given string in UTF-16 characters.
     * A surrogate pair is counted as two characters.
     *
     * @param mixed $string
     * @return integer
     */
    public static function utf16_strlen($string): int {
        $string = (string)$string;
        if ($string === '') {
            return 0;
        }
        $utf16string = iconv('utf-8', 'utf-16le', $string);
        return strlen($utf16string) / 2;
    }

    /**
     * Download a manifest file.
     *
     * @throws moodle_exception
     */
    public static function download(): void {
        $manifestfilepath = self::create_manifest();
        // Download manifest file.
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename=manifest.zip');
        header('Content-length: ' . filesize($manifestfilepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($manifestfilepath);
    }

    /**
     * Generate a manifest file.
     *
     * @return string The file path to the manifest file generated.
     * @throws msteams_exception
     */
    public static function create_manifest(): string {
        global $CFG;
        require_once($CFG->libdir . '/filestorage/zip_archive.php');

        // Check if all settings are valid.
        $verifier = new verifier();
        if (!$verifier->execute()) {
            throw new manifest_exception($verifier->get_report());
        }

        // Prepare a manifest folder.
        $manifestdir = realpath($CFG->tempdir);
        if (substr($manifestdir, -1) !== DIRECTORY_SEPARATOR) {
            $manifestdir .= DIRECTORY_SEPARATOR;
        }

        // Archive a manifest file and related files.
        $output = new zip_output();
        $zipfilepath = $manifestdir . 'manifest-' . strtolower(random_string()) . '.zip';
        if (!$output->open($zipfilepath)) {
            throw new manifest_exception(get_string('error:manifest_createzip', 'totara_msteams'));
        }
        $generator = new generator();
        $generator->generate_files($output);
        $output->close();

        return $zipfilepath;
    }
}
