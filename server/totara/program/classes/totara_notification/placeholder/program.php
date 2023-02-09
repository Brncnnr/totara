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
 * @author  Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_program
 */
namespace totara_program\totara_notification\placeholder;

use coding_exception;
use html_writer;
use moodle_url;
use stdClass;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class program extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /**
     * @var ?stdClass
     */
    private $record;

    /**
     * program constructor.
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int  $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        global $DB;

        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $program_record = $DB->get_record('prog', ['id' => $id]) ?: null;
            $instance = new static($program_record);
            self::add_instance_to_cache($id, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('full_name', get_string('fullname', 'totara_program')),
            option::create('full_name_link', get_string('full_name_linked', 'totara_program')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return null !== $this->record;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if (null === $this->record) {
            throw new coding_exception("The program record is empty");
        }

        switch ($key) {
            case 'full_name':
                return format_string($this->record->fullname);
            case 'full_name_link':
                $url = new moodle_url('/totara/program/view.php', ['id' => $this->record->id]);
                return html_writer::link($url, format_string($this->record->fullname));
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