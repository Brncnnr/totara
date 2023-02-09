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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\filter;

use coding_exception;
use core\orm\entity\filter\filter;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\raw_field;

/**
 * Filter by applicant name
 */
class applicant_name extends filter {

    /**
     * @var string
     */
    protected $applicant_table_alias;

    /**
     * @param string $applicant_table_alias
     */
    public function __construct(string $applicant_table_alias = 'applicant') {
        parent::__construct([]);
        $this->applicant_table_alias = $applicant_table_alias;
    }

    /**
     * @inheritDoc
     */
    public function apply(): void {
        if (empty($this->value)) {
            throw new coding_exception('applicant fullname filter must have a string for value');
        }

        $name_concat = builder::get_db()->sql_concat_join("' '", totara_get_all_user_name_fields_join($this->applicant_table_alias, null, true));
        $this->builder->where(new raw_field($name_concat), 'ilike', $this->value);
    }
}