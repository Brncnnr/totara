<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\totara_notification\placeholder;

use coding_exception;
use core\orm\query\builder;
use stdClass;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class enrolment extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var stdClass|null
     */
    protected $record;

    /**
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int $workspace_id
     * @param int $user_id
     * @return static
     */
    public static function from_workspace_id_and_user_id(int $workspace_id, int $user_id): self {
        $cache_key = $workspace_id . ':' . $user_id;
        $instance = self::get_cached_instance($cache_key);
        if (!$instance) {
            $sub_query = builder::table('user_enrolments', 'ue')
                ->select('*')
                ->add_select_raw(
                    "CASE WHEN ue.timestart IS NULL OR ue.timestart = 0 THEN ue.timecreated
                         ELSE ue.timestart
                     END AS time_joined")
                ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
                ->where('ue.userid', $user_id)
                ->where('e.courseid', $workspace_id)
                ->where(function (builder $builder) {
                    $builder->where('ue.timeend', 0)
                        ->or_where('ue.timeend', '>', time());
                });

            $record = builder::table($sub_query)
                ->as('ss')
                ->select('*')
                ->order_by('time_joined')
                ->first();

            $instance = new static($record ? (object) $record : null);
            self::add_instance_to_cache($cache_key, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('join_date', get_string('placeholder_workspace_join_date', 'container_workspace')),
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->record === null) {
            throw new coding_exception("The workspace enrolment record is empty");
        }

        if ($key === 'join_date') {
            return userdate($this->record->time_joined ?? '');
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->record !== null;
    }
}
