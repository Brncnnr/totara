<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\formatter\field;

use context;
use core\webapi\formatter\field\base;
use totara_engage\time\date_time;

/**
 * Formating date time
 */
final class date_field_formatter extends base {
    /**
     * @var int|null
     */
    private $timemodified;

    /**
     * date_field_formatter constructor.
     * @param string|null $format
     * @param context $context
     */
    public function __construct(?string $format, context $context) {
        parent::__construct($format, $context);
        $this->timemodified = null;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_timemodified(int $value): void {
        $this->timemodified = $value;
    }

    /**
     * @param int|null $value
     * @return string
     */
    protected function get_default_format($value): string {
        if (null === $value || 0 === $value) {
            debugging("Time value is invalid", DEBUG_DEVELOPER);
            return '';
        }

        $created = new date_time($value);
        $timecreated = $created->get_readable_string();

        if (null === $this->timemodified) {
            return get_string('createtime', 'totara_engage', $timecreated);
        }

        $modified = new date_time($this->timemodified);
        $timemodified = $modified->get_readable_string();

        $a = new \stdClass();
        $a->timecreated = $timecreated;
        $a->timemodifed = $timemodified;

        return get_string('createtimewithupdatetime', 'totara_engage', $a);
    }
}