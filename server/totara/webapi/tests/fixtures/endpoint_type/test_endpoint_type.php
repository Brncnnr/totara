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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\endpoint_type;

defined('MOODLE_INTERNAL') || die();

class test_endpoint_type extends base {

    public $validate_schema;
    public $allow_direct_queries;
    public $allow_persistent_queries;
    public $require_session;

    /**
     * @inheridDoc
     */
    public static function get_name(): string {
        return 'test';
    }

    /**
     * @inheritDoc
     */
    public function validate_schema(): bool {
        return $this->validate_schema;
    }

    /**
     * @inheritDoc
     */
    public function allow_direct_queries(): bool {
        return $this->allow_direct_queries;
    }

    /**
     * @inheritDoc
     */
    public function allow_persistent_queries(): bool {
        return $this->allow_persistent_queries;
    }

    /**
     * @inheritDoc
     */
    public function require_sesskey(): bool {
        return $this->require_session;
    }

}