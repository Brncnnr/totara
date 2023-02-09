<?php
/*
 *
 * This file is part of Totara Core
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_xapi
 *
 */

namespace totara_xapi\event;

use core\event\base;
use totara_xapi\entity\xapi_statement as xapi_statement_entity;
use totara_xapi\model\xapi_statement;
use context_system;

class xapi_statement_created extends base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = xapi_statement_entity::TABLE;
    }

    public static function create_from_xapi_statement(xapi_statement $xapi_statement) {
        $data = [
            'objectid' => $xapi_statement->get_id(),
            'relateduserid' => null,
            'other' => [
                'statement' => $xapi_statement->get_raw_statement(),
                'client_id' => $xapi_statement->client_id
            ],
            'userid' => $xapi_statement->user_id,
            'context' => context_system::instance(),
        ];

        return static::create($data);
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_xapi_statement_created', 'totara_xapi');
    }

    public function get_description() {
        return "An xAPI statement relating to the user with id '$this->userid' has been created.";
    }
}