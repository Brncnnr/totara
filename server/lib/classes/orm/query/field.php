<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query;

use coding_exception;
use core\dml\sql;

/**
 * A wrapper around string form of field name injected into sql,
 *
 * used as a holding bag for a field name, as, agg function (if given)
 * provides a basic sanity check for the field name against a simple regular expression,
 * also serves a purpose of deferred conversion from object to string.
 *
 * @package core\orm\query
 */
class field extends raw_field {

    /**
     * field enforces that validation is performed on init, unless "raw" sql is passed
     *
     * @param sql|string $field
     * @param builder|null $builder
     */
    public function __construct($field, ?builder $builder = null) {
        $validate = true;

        if ($field instanceof sql) {
            $sql = $field->to_named_params('qb_field_param');
            $params = $sql->get_params();
            $field = $sql->get_sql();
            $validate = false;
        }

        parent::__construct($field, $builder);
        $this->is_raw = !$validate;

        $this->set_params($params ?? []);

        if ($validate && !$this->validate()) {
            throw new coding_exception('Invalid field passed: "' . $this->get_field_as_is() . '"');
        }
    }
}
