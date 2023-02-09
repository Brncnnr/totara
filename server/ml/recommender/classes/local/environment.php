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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\local;

use coding_exception;

/**
 * Class environment
 * @package ml_recommender
 */
final class environment {
    /**
     * Preventing this class from being constructed
     * environment constructor.
     */
    private function __construct() {
    }

    /**
     * @return string
     */
    private static function get_ml_data_root_path(): string {
        global $CFG;

        $data_path = get_config('ml_recommender', 'data_path');
        if ($data_path == '' || $data_path === false) {
            $data_path = "{$CFG->dataroot}/recommender";
        }

        return $data_path;
    }

    /**
     * Returning the result count config.
     *
     * @return int
     */
    public static function get_user_result_count(): int {
        $result_count = get_config('ml_recommender', 'user_result_count');
        if (false === $result_count) {
            $result_count = 25;
        }

        return (int) $result_count;
    }

    /**
     * Returning the related count config.
     *
     * @return int
     */
    public static function get_item_result_count(): int {
        $result_count = get_config('ml_recommender', 'item_result_count');

        if (false === $result_count) {
            $result_count = 15;
        }

        return (int) $result_count;
    }

    /**
     * Path to Python executable.
     *
     * @return string
     */
    public static function get_py3path(): string {
        $py3path = get_config('ml_recommender', 'py3path');
        if (empty($py3path)) {
            $py3path = '/usr/bin/python3';
        }

        return $py3path;
    }

    /**
     * Recommender query type.
     *
     * @return string
     */
    public static function get_query(): string {
        $query = get_config('ml_recommender', 'query');
        if (empty($query)) {
            $query = 'mf';
        }

        return $query;
    }

    /**
     * Number of threads/cores that recommender engine may use.
     *
     * @return int
     */
    public static function get_threads(): int {
        $threads = get_config('ml_recommender', 'threads');
        if (empty($threads)) {
            $threads = 2;
        }

        return (int) $threads;
    }

    /**
     * Number of related items to show in Related pane.
     *
     * @return int
     */
    public static function get_related_items_count(): int {
        $related_items_count = get_config('ml_recommender', 'related_items_count');

        if (false === $related_items_count) {
            $related_items_count = 3;
        }

        return (int) $related_items_count;
    }

    /**
     * Number of weeks worth of user-item interactions to analyse.
     *
     * @return int
     */
    public static function get_interactions_period(): int {
        $interactions_period = get_config('ml_recommender', 'interactions_period');

        if (false === $interactions_period) {
            $interactions_period = 16;
        }

        return (int) $interactions_period;
    }

    /**
     * Get export path
     * @return string
     */
    public static function get_data_path(): string {
        return rtrim(static::get_ml_data_root_path(), '/\\') . '/data/';
    }

    /**
     * Get temp path
     * @return string
     */
    public static function get_temp_path(): string {
        return rtrim(static::get_ml_data_root_path(), '/\\') . '/temp/';
    }

    /**
     * Get backup path
     * @return string
     */
    public static function get_backup_path(): string {
        return rtrim(static::get_ml_data_root_path(), '/\\') . '/backup/';
    }

    /**
     * Make sure that data path is accessible, is not empty and has no accidental dangerous misconfiguration
     */
    public static function enforce_data_path_sanity() {
        global $CFG;
        $ml_data_root = rtrim(static::get_ml_data_root_path(), '/\\');
        if (strlen($ml_data_root) < 3) {
            throw new coding_exception('Recommenders data path (ml_recommender/data_path) must be 3 or more characters long');
        }

        if ($ml_data_root == $CFG->dataroot) {
            throw new coding_exception('Recommenders data path (ml_recommender/data_path) cannot be the same as site data root');
        }

        if (!is_dir($ml_data_root) || !is_writable($ml_data_root)) {
            if (!mkdir($ml_data_root, $CFG->directorypermissions, true)) {
                throw new coding_exception('Error creating ML data root directory (ml_recommender/data_path)');
            }
        }
    }
}
