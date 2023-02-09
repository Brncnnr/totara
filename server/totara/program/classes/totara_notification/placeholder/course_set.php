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
 * @package totara_notification
 */
namespace totara_program\totara_notification\placeholder;

use coding_exception;
use stdClass;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class course_set extends single_emptiable_placeholder {
    /**
     * @var ?stdClass
     */
    private $record;

    /**
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int  $id
     * @param bool $strict
     *
     * @return self
     */
    public static function from_id(int $id, bool $strict = false): self {
        global $DB;

        $strictness = $strict ? MUST_EXIST : IGNORE_MISSING;
        $course_set_record = $DB->get_record('prog_courseset', ['id' => $id], '*', $strictness);
        $record = $course_set_record ?: null;

        return new static($record);
    }


    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create('label', get_string('setlabel', 'totara_program')),
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
        switch ($key) {
            case 'label':
                return $this->record->label ? format_string($this->record->label) : '';
        }

        throw new coding_exception("Invalid key '{$key}'");
    }
}