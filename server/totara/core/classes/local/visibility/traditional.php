<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql;

/**
 * Traditional visibility resolver abstract class.
 *
 * Designed to centralise common logic when processing traditional visibility.
 * Importantly, it must conform to the resolver interface.
 *
 * @internal
 */
abstract class traditional extends base implements resolver {

    /**
     * @inheritDoc
     * @return string
     */
    public function sql_field_visible(): string {
        return 'visible';
    }

    /**
     * Generates an traditional visibility SQL snippet
     *
     * @param int $userid
     * @param string $field_id
     * @param string $field_visible
     * @return sql
     */
    protected function get_visibility_sql(int $userid, string $field_id, string $field_visible): sql {
        return new sql("{$field_visible} = 1");
    }

}