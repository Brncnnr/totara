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

use core\format;
use \core\webapi\type_resolver;
use \core\webapi\execution_context;
use \mobile_findlearning\formatter\catalog_item_formatter as item_formatter;
use \mobile_findlearning\item_mobile as mobile_item;
use \totara_catalog\provider_handler;

class catalog_item extends type_resolver {

    /**
     * Resolve program fields
     *
     * @param string $field
     * @param stdClass $object - the dataobject used to create a mobile item
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $object, array $args, execution_context $ec) {
        global $CFG;

        try {
            $item = mobile_item::create($object);
        } catch (\throwable $e) {
            throw new \coding_exception('Only mobile_item catalog objects are accepted: ' . gettype($object));
        }

        $format = $args['format'] ?? null;
        $context = self::get_item_context($object);
        $data = (object) $item->get_template_data();

        // Any field customisations we need happen here in this switch.
        switch ($field) {
            case 'id':
                return $object->id;
            case 'itemid':
                return $object->objectid;
            case 'item_type':
                return $data->objecttype;
            case 'image_enabled':
                return isset($data->image_enabled) ?? false;
            case 'summary':
                // This one needs a bit of special handling.
                $data->objectid = $object->objectid;
                $data->summary = null;
                if ($data->objecttype == 'course') {
                    // If we have the raw data use it, otherwise stick with null.
                    $data->summary = $object->raw_summary ?? null;
                    $data->summaryformat = $object->raw_summaryformat ?? null;
                }
                break;
            case 'summary_format':
                $data->summary_format = null;
                if ($data->objecttype == 'course' && !empty($object->raw_summaryformat)) {
                    $data->summary_format = self::format_summary_format($object->raw_summaryformat);
                }
                return $data->summary_format;
            case 'view_url':
                if ($data->objecttype == 'course') {
                    // Skip the details url used by everything else, the course one has a LOT of conditions in it.
                    return course_get_url($object->objectid)->out();
                } else {
                    $provider = provider_handler::instance()->get_provider($data->objecttype);

                    $details = $provider->get_details_link($object->objectid);
                    return $details->button->url ?? null;
                }
            case 'image_url':
            case 'image_alt':
                $enabled = isset($data->image_enabled) ?? false;
                if ($enabled) {
                    $image = $data->image;
                    $data->image_url = $image->url;
                    $data->image_alt = $image->alt;
                } else {
                    return null;
                }
                break;
            case 'description_enabled':
                return isset($data->description_enabled) ?? false;
            case 'description':
                $enabled = isset($data->description_enabled) ?? false;
                if (!$enabled || empty($data->description)) {
                    return null;
                }
                break;
        }

        $formatter = new item_formatter($data, $context);
        $formatted = $formatter->format($field, $format ?? '');
        if (in_array($field, ['image_url', 'summary']) && $formatted) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);

            if ($field == 'image_url') {
                // Remove all URL arguments.
                $key = "~\?.*=.*~";
                $formatted = preg_replace($key, '', $formatted);
            }
        }

        return $formatted;
    }

    /**
     * Extract the summary from the list of additional dataholders.
     */
    private static function find_dataholder_contents($dataholders, $key = 'summary_rich'): string {
        $data = '';
        foreach ($dataholders as $dataholder) {
            if (isset($dataholder[$key])) {
                $data = $dataholder[$key];
                break;
            }
        }

        return $data;
    }

    /**
     * Transform the summary format field into a string for mobile use.
     *
     * @param int $format - The raw data from course.summaryformat
     * @return string     - The formatted string relating to $format
     */
    private static function format_summary_format($format) {
        return format::from_moodle($format);
    }

    /**
     * @param stdClass $object
     * @return \context
     */
    private static function get_item_context($object) {
        switch ($object->objecttype) {
            case 'course':
            case 'playlist':
            case 'engage_article':
                return \context::instance_by_id($object->contextid, MUST_EXIST);
            case 'program':
            case 'certification':
            default:
                throw new \coding_exception('Unexpected mobile_item type, mobile catalog does not support: ' . $object->objecttype);
        }
    }
}
