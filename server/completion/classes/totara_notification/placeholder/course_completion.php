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
use completion_info;
use core\entity\course_completion as course_completion_entity;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class course_completion extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var course_completion_entity|null
     */
    protected $entity;

    /**
     * course_completion constructor.
     * @param course_completion_entity|null $entity
     */
    public function __construct(?course_completion_entity $entity) {
        $this->entity = $entity;
    }

    /**
     * @param int $course_id
     * @param int $user_id
     *
     * @return self
     */
    public static function from_course_id_and_user_id(int $course_id, int $user_id): self {
        $cache_key = $course_id . ':' . $user_id;
        $instance = self::get_cached_instance($cache_key);
        if (!$instance) {
            $entity = course_completion_entity::repository()
                ->where('course', $course_id)
                ->where('userid', $user_id)
                ->order_by('id')
                ->first();
            $instance = new static($entity);
            self::add_instance_to_cache($cache_key, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('completion_date', get_string('placeholder_course_completion_date', 'completion')),
            option::create('due_date', get_string('placeholder_course_due_date', 'completion')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        if ($this->entity === null) {
            return false;
        }

        if ($key === 'completion_date' && $this->entity->timecompleted === null) {
            return false;
        }

        if ($key === 'due_date' && $this->entity->duedate === null) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        /** @var \core_config $CFG */
        global $CFG;
        require_once($CFG->dirroot . '/lib/completionlib.php');

        if ($this->entity === null) {
            throw new coding_exception("The course completion entity record is empty");
        }

        switch ($key) {
            case 'completion_date':
                return empty($this->entity->timecompleted) ? '' : userdate($this->entity->timecompleted);

            case 'due_date':
                if (!completion_info::is_enabled_for_site()
                    || $this->entity->course_instance->enablecompletion != COMPLETION_ENABLED
                    || empty($this->entity->duedate)) {
                    return '';
                }

                $date_time_format = get_string("strftimedatefulllong", "langconfig");
                return userdate(
                    $this->entity->duedate,
                    $date_time_format,
                    99, // Use current user's timezone which should be the notification recipient's one.
                    false
                );
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

}
