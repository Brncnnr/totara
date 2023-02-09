<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_completionimport
 */

namespace totara_completionimport\event;

use core\event\base;

/**
 * Event triggered when course completion records are imported in bulk
 */
class bulk_course_completionimport extends base {

    /** @var string event payload key */
    const PAYLOAD_KEY = 'user_course_completions';

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param array $user_course_completions Array with user_id as key and list of completed course_ids as value
     * @return bulk_course_completionimport
     */
    public static function create_from_list(array $user_course_completions) {
        return bulk_course_completionimport::create(
            [
                'context' => \context_system::instance(),
                'other' => [self::PAYLOAD_KEY => $user_course_completions],
            ]
        );
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_course_completion_imported', 'totara_completionimport');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "Completion records for multiple users";
    }

    /**
     * Return the user course completions
     *@return array
     */
    public function get_completions(): array {
        return $this->other[self::PAYLOAD_KEY];
    }

}
