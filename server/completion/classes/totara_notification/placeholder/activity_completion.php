<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_completion
 * @category totara_notification
 */
namespace core_completion\totara_notification\placeholder;

use coding_exception;
use stdClass;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class activity_completion extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var ?stdClass
     */
    private $record;

    /**
     * @var int
     */
    private $user_id;

    private const COMPLETED_STATES = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS, COMPLETION_COMPLETE_FAIL];

    /**
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int $cm_id
     * @param int $user_id
     *
     * @return self
     */
    public static function from_activity_id_and_user_id(int $cm_id, int $user_id): self {
        global $DB;

        $cache_key = $cm_id . ':' . $user_id;
        $instance = self::get_cached_instance($cache_key);
        if (!$instance) {
            $record = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cm_id, 'userid' => $user_id]) ?: null;
            $instance = new static($record);
            self::add_instance_to_cache($cache_key, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('completion_date', get_string('placeholder_activity_completion_date', 'completion')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        if ($this->record === null) {
            return false;
        }

        if ($key === 'completion_date' && !in_array($this->record->completionstate, self::COMPLETED_STATES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->record === null) {
            // If user hasn't started progress, no completion record exists. Returning '' for all keys
            return '';
        }

        switch ($key) {
            case 'completion_date':
                if (!in_array($this->record->completionstate, self::COMPLETED_STATES)) {
                    return '';
                }

                // timecompleted column are only set in some instances.
                $timecompleted = '';
                if (!empty($this->record->timecompleted)) {
                    $timecompleted = $this->record->timecompleted;
                } else if (!empty($this->record->timemodified)) {
                    $timecompleted = $this->record->timemodified;
                }

                return empty($timecompleted) ? '' : userdate($timecompleted);
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

}
