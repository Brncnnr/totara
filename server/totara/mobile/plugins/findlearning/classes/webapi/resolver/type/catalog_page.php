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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning\webapi\resolver\type;

use \core\webapi\type_resolver;
use \core\webapi\execution_context;
use \mobile_findlearning\formatter\catalog_page_formatter as page_formatter;
use \mobile_findlearning\item_mobile as mobile_item;

class catalog_page extends type_resolver {

    /**
     * Resolve program fields
     *
     * @param string $field
     * @param stdClass $page
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $page, array $args, execution_context $ec) {
        global $DB;

        $format = $args['format'] ?? null;
        $context = \context_system::instance();

        // Any field customisations we need happen here in this switch.
        $data = new \stdClass();
        switch ($field) {
            case 'max_count':
                $data->max_count = $page->maxcount;
                break;
            case 'limit_from':
                $data->limit_from = $page->limitfrom;
                break;
            case 'final_records':
                $data->final_records = $page->endofrecords;
                break;
            case 'items':
                // So we need some extra steps to get raw summary info for courses.
                $courseids = [];
                foreach ($page->objects as $object) {
                    if ($object->objecttype == 'course') {
                        $courseids[] = $object->objectid;
                    }

                    // Make sure the fields are set for all objects.
                    $object->raw_summary = null;
                    $object->raw_summaryformat = null;
                }

                // On the off chance these are all resources etc, just carry on.
                if (!empty($courseids)) {
                    // Otherwise get the raw data and insert the data into course objects.
                    list($insql, $params) = $DB->get_in_or_equal($courseids);
                    if ($courses = $DB->get_records_select('course', "id {$insql}", $params, '', 'id, summary, summaryformat')) {
                        foreach ($page->objects as $object) {
                            if ($object->objecttype == 'course' && !empty($courses[$object->objectid])) {
                                $object->raw_summary = $courses[$object->objectid]->summary;
                                $object->raw_summaryformat = $courses[$object->objectid]->summaryformat;
                            }
                        }
                    }
                }

                return $page->objects;
        }

        $formatter = new page_formatter($data, $context);
        return $formatter->format($field, $format);
    }
}
