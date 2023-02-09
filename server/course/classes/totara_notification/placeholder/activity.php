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
use core\entity\user_enrolment as user_enrolment_entity;
use core\orm\query\builder;
use html_writer;
use moodle_url;
use stdClass;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class activity extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var ?stdClass
     */
    private $record;

    /**
     * _activity constructor.
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int $cmid
     *
     * @return self
     */
    public static function from_id(int $cmid): self {
        global $DB;

        $instance = self::get_cached_instance($cmid);
        if (!$instance) {
            $cm = get_coursemodule_from_id('', $cmid) ?: null;
            $instance = new static($cm);
            self::add_instance_to_cache($cmid, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('name', get_string('placeholder_activity_name', 'moodle')),
            option::create('name_link', get_string('placeholder_activity_name_linked', 'moodle')),
            option::create('type', get_string('placeholder_activity_type', 'moodle')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->record !== null;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->record === null) {
            throw new coding_exception("The course activity record is empty");
        }

        switch ($key) {
            case 'name':
                return $this->record->name ?? '';
            case 'name_link':
                $url = new moodle_url("/mod/{$this->record->modname}/view.php", ['id' => $this->record->id]);
                return html_writer::link($url, format_string($this->record->name));
            case 'type':
                return get_string('modulename', $this->record->modname);
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('name_link' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }
}
