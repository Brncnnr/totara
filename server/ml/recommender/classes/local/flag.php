<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local;

use coding_exception;

/**
 * Class flag manage possible multi-processing conflicts
 */
class flag {

    const ML = 'ml';
    const EXPORT = 'export';
    const IMPORT = 'import';

    /**
     * Set of "lock" file names indicating various stages of workflow
     */
    private const ML_STARTED = 'ml_started';
    private const ML_COMPLETED = 'ml_completed';
    private const EXPORT_STARTED = 'export_started';
    private const EXPORT_COMPLETED = 'export_completed';
    private const IMPORT_STARTED = 'import_started';
    private const IMPORT_COMPLETED = 'import_completed';

    /**
     * Return true if some process is started but haven't finished
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     * @return bool returns true if starting flag exists and finishing is not
     */
    public static function in_progress(string $process, string $custom_path = ""): bool {
        $path = (empty($custom_path)) ? environment::get_data_path() : $custom_path;
        [$start, $complete] = static::get_flags($process);

        return file_exists($path . $start) && !file_exists($path . $complete);
    }

    /**
     * Return true if the process has been completed.
     *
     * @param string $process
     * @param string $custom_path
     * @return bool
     */
    public static function is_complete(string $process, string $custom_path = ""): bool {
        $path = (empty($custom_path)) ? environment::get_data_path() : $custom_path;
        [$start, $complete] = static::get_flags($process);

        return file_exists($path . $complete);
    }

    /**
     * If process is in progress, will output error and exit
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     */
    public static function must_not_in_progress(string $process, string $custom_path = "") {
        if (flag::in_progress($process, $custom_path)) {
            flag::error($process, $custom_path);
        }
    }

    /**
     * Put start flag indicating start of process
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     * @return bool If flag was put successfully
     */
    public static function start(string $process, string $custom_path = ""): bool {
        $path = (empty($custom_path)) ? environment::get_data_path() : $custom_path;
        [$start] = static::get_flags($process);

        if (file_put_contents($path . $start, time())) {
            return true;
        }
        return false;
    }

    /**
     * Tries to put start flag and throws exception if cannot
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     */
    public static function must_start(string $process, string $custom_path = "") {
        if (!static::start($process, $custom_path)) {
            throw new coding_exception("Cannot write start flag: $process");
        }
    }

    /**
     * Put start flag indicating start of process and return result
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     * @return bool If flag was put successfully
     */
    public static function complete(string $process, string $custom_path = ""): bool {
        $path = (empty($custom_path)) ? environment::get_data_path() : $custom_path;
        [$start, $complete] = static::get_flags($process);

        if (file_put_contents($path . $complete, time())) {
            return true;
        }
        return false;
    }

    /**
     * Tries to put complete flag and throws exception if cannot
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     */
    public static function must_complete(string $process, string $custom_path = "") {
        if (!static::complete($process, $custom_path)) {
            throw new coding_exception("Cannot write complete flag: $process");
        }
    }

    /**
     * Returns corresponding start and finish flag names
     *
     * @param string $process
     * @return string[]
     */
    protected static function get_flags(string $process): array {
        $map = [
            self::ML => [self::ML_STARTED, self::ML_COMPLETED],
            self::EXPORT => [self::EXPORT_STARTED, self::EXPORT_COMPLETED],
            self::IMPORT => [self::IMPORT_STARTED, self::IMPORT_COMPLETED],
        ];

        if (!isset($map[$process])) {
            throw new coding_exception('Failed to check flag for unknown process: ' . $process);
        }

        return $map[$process];
    }

    /**
     * Displays problem with lock, lock timestamp, and exit
     *
     * @param string $process     name of process to check (this class constant)
     * @param string $custom_path Use another folder to check for flags instead of data folder
     */
    protected static function error(string $process, string $custom_path = "") {
        $path = (empty($custom_path)) ? environment::get_data_path() : $custom_path;
        [$start] = static::get_flags($process);

        $timestamp = 0;
        $filepath = $path . $start;
        if (file_exists($path . $start) && is_readable($path . $start)) {
            $timestamp = file_get_contents($filepath);
        }
        $date = $timestamp ? date("Y-m-d H:i:s", $timestamp) : '(unknown)';
        $fullmessage = "{$process} not finished. Created: $date. Location: {$filepath}.";
        throw new coding_exception(
            $fullmessage . ' Possibly parallel process is still running, or it was crashed. ' .
            'Use export CLI script with --force to restart.'
        );
    }
}