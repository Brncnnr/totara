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
 * @package core_course
 * @category totara_notification
 */

namespace core_course\totara_notification\placeholder;

use coding_exception;
use core\entity\course as course_entity;
use html_writer;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\customfield_options;
use totara_notification\placeholder\option;

class course extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var course_entity|null
     */
    protected $entity;

    /**
     * course constructor.
     * @param course_entity|null $entity
     */
    public function __construct(?course_entity $entity) {
        $this->entity = $entity;
    }

    /**
     * @param int  $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $entity = course_entity::repository()->find($id);
            $instance = new static($entity);
            self::add_instance_to_cache($id, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return array_merge(
            customfield_options::get_options('course'),
            [
                option::create('full_name', get_string('placeholder_course_fullname', 'moodle')),
                option::create('full_name_link', get_string('placeholder_course_fullname_linked', 'moodle')),
            ]
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->entity !== null;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->entity === null) {
            throw new coding_exception("The course entity record is empty");
        }

        switch ($key) {
            case 'full_name':
                return $this->entity->fullname;
            case 'full_name_link':
                $url = new moodle_url('/course/view.php', ['id' => $this->entity->id]);
                return html_writer::link($url, format_string($this->entity->fullname));
        }

        $field_map = customfield_options::get_key_field_map('course');
        if (isset($field_map[$key])) {
            return format_string(customfield_options::get_field_value($this->entity->id, $field_map[$key], 'course', 'course'));
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('full_name_link' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }

}