<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
namespace totara_engage\entity;

use core\orm\entity\entity;
use totara_engage\access\access;

/**
 * @property int        $id
 * @property int        $instanceid
 * @property string     $name
 * @property string     $resourcetype
 * @property int        $userid
 * @property int        $access
 * @property int        $timecreated
 * @property int        $timemodified
 * @property string     $extra
 * @property int        $countusage
 * @property int        $contextid
 */
final class engage_resource extends entity {
    /**
     * @var string
     */
    public const TABLE = 'engage_resource';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @param array $record
     * @return engage_resource
     */
    public static function from_record(array $record): engage_resource {
        $resource = new static();

        foreach ($record as $key => $value) {
            if (!$resource->db_column_exists($key)) {
                continue;
            }

            $resource->set_attribute($key, $value);
        }

        $resource->reset_dirty();
        return $resource;
    }

    /**
     * @param string|int $value
     * @return void
     */
    protected function set_access_attribute($value): void {
        if (!access::is_valid($value)) {
            throw new \coding_exception("Cannot set the access value that is invalid '{$value}'");
        }

        $this->set_attribute_raw('access', $value);
    }

    /**
     * @return array
     */
    public function get_json_decoded(): array {
        $extra = $this->extra;

        if (null == $this->extra) {
            return [];
        }

        $extra = json_decode($extra, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \coding_exception("Cannot decode the json data due to " . json_last_error_msg());
        }

        return $extra;
    }

    /**
     * @param \stdClass|\JsonSerializable|array
     * @return void
     */
    protected function set_extra_attribute($item): void {
        if (is_string($item)) {
            // If it is a string, then we treated it differently, as it could be an encoded json string.
            if ('' === $item) {
                $this->set_attribute_raw('extra', null);
                return;
            }

            json_decode($item);
            if (JSON_ERROR_NONE !== json_last_error()) {
                // Righty, the string $item is not a json.
                $encoded = json_encode($item);
                $this->set_attribute_raw('extra', $encoded);
            } else {
                $this->set_attribute_raw('extra', $item);
            }

            return;
        }

        $extra = null;

        if (null !== $item) {
            if (($item instanceof \JsonSerializable) || is_array($item)) {
                $extra = json_encode($item);
            } else if ($item instanceof \stdClass) {
                $data = get_object_vars($item);
                $extra = json_encode($data);
            } else {
                debugging("The value of parameter \$item is not able to be json_encoded", DEBUG_DEVELOPER);
            }

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \coding_exception("Cannot encode the item into json due to: " . json_last_error_msg());
            }
        }

        $this->set_attribute_raw('extra', $extra);
    }
}