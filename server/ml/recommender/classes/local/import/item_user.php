<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local\import;

use Iterator;
use stdClass;
use ml_recommender\local\unique_id;

/**
 *
 * Upload recommendations per user from csv generated by the python recommender system.
 *
 * @package ml_recommender
 * @deprecated since Totara 17.0 ml_recommender importer has been deprecated.
 */
class item_user extends import {
    /**
     * @return string
     */
    public function get_name(): string {
        return 'i2u';
    }

    /**
     * @param Iterator $reader
     * @param int      $time
     *
     * @return void
     */
    public function import(Iterator $reader, int $time = 0): void {
        global $DB;

        foreach ($reader as $fields) {
            // A score of -1 means recommender couldn't find any interactions for this user, therefore no recommendations.
            $score = round((float) $fields['ranking'], 12);
            if ($score === -1.0) {
                continue;
            }

            $unique_id = $fields['iid'];
            [$component, $item_id] = unique_id::normalise_unique_id($unique_id);

            $record = new stdClass();
            $record->user_id = (int) $fields['uid'];
            $record->unique_id = $unique_id;
            $record->item_id = (int) $item_id;
            $record->component = $component;
            $record->area = null;

            $record->score = (float)$score;
            $record->seen = (int) 0;
            $record->time_created = $time ?: time();

            $DB->insert_record('ml_recommender_users', $record);
        }
    }

    /**
     * @param int $timestamp
     * @return void
     */
    public function clean(int $timestamp) {
        global $DB;
        $DB->execute('DELETE FROM {ml_recommender_users} WHERE time_created < :timestamp', ['timestamp' => $timestamp]);
    }
}