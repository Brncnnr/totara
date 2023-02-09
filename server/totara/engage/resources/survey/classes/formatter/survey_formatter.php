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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\formatter;

use core\webapi\formatter\formatter;
use engage_survey\totara_engage\resource\survey;
use totara_engage\formatter\field\date_field_formatter;

/**
 * Formatter for survey.
 */
final class survey_formatter extends formatter {
    /**
     * survey_formatter constructor.
     * @param survey $survey
     */
    public function __construct(survey $survey) {
        $record = new \stdClass();

        $record->timecreated = $survey->get_timecreated();
        $record->timemodified = $survey->get_timemodified();
        parent::__construct($record, $survey->get_context());
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('timedescription' === $field) {
            return parent::get_field('timecreated');
        }

        return parent::get_field($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('timedescription' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;
        return [
            'timedescription' => function (int $value, date_field_formatter $formatter) use ($that): string {
                if (null !== $that->object->timemodified && 0 !== $that->object->timemodified) {
                    $formatter->set_timemodified($that->object->timemodified);
                }

                return $formatter->format($value);
            }
        ];
    }
}