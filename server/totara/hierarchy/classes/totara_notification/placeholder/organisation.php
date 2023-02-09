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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_hierarchy
 */
namespace totara_hierarchy\totara_notification\placeholder;

use coding_exception;
use context_system;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use stdClass;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

global $CFG;
require_once("{$CFG->dirroot}/totara/hierarchy/prefix/organisation/lib.php");

class organisation extends single_emptiable_placeholder {

    use placeholder_instance_cache;

    /**
     * @var ?stdClass
     */
    private $record;

    /**
     * organisation constructor.
     * @param stdClass|null $record
     */
    public function __construct(?stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $hierarchy = \hierarchy::load_hierarchy('organisation');
            $record = $hierarchy->get_item($id);
            $instance = new static($record);
            self::add_instance_to_cache($id, $instance);
        }
        return $instance;
    }

    /**
     * @param stdClass $record
     *
     * @return self
     */
    public static function from_record(stdClass $record): self {
        // Use the new record, ignoring cache - the record might have been modified, and it costs almost
        // nothing to create a new one.
        $instance = new static($record);
        self::add_instance_to_cache($record->id, $instance);
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create(
                'short_name',
                get_string('organisationshortname', 'totara_hierarchy')
            ),
            option::create(
                'full_name',
                get_string('organisationfullname', 'totara_hierarchy')
            ),
            option::create(
                'id_number',
                get_string('organisationidnumber', 'totara_hierarchy')
            ),
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
            throw new coding_exception("The organisation record is empty");
        }

        $context = context_system::instance();
        $formatter = new string_field_formatter(format::FORMAT_PLAIN, $context);

        switch ($key) {
            case 'short_name':
                if (is_null($this->record->shortname)) {
                    // No available data for short_name
                    return NULL;
                }
                return $formatter->format($this->record->shortname);
            case 'full_name':
                return $formatter->format($this->record->fullname);
            case 'id_number':
                if (is_null($this->record->idnumber)) {
                    // No available data for id_number
                    return NULL;
                }
                return $formatter->format($this->record->idnumber);
        }

        throw new coding_exception("Invalid key '{$key}'");
    }
}