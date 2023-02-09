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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use core\orm\entity\filter\filter;
use core\orm\query\builder;
use core\orm\query\raw_field;

class subject_instances_search_term extends filter {

    /**
     * @var string
     */
    protected $activity_alias;

    /**
     * @var string
     */
    protected $user_alias;

    public function __construct(string $activity_alias = 'a', string $user_alias = 'su') {
        parent::__construct([]);
        $this->activity_alias = $activity_alias;
        $this->user_alias = $user_alias;
    }

    /**
     * Filter those records where user fullname OR activity name matches the search term.
     */
    public function apply(): void {
        if (trim($this->value) === '') {
            return;
        }
        $this->builder->where(function (builder $builder) {
            $user_fullname = new raw_field(
                $builder::get_db()->sql_concat_join(
                    "' '",
                    totara_get_all_user_name_fields_join($this->user_alias, null, true)
                )
            );
            $builder->or_where($user_fullname, 'ILIKE', $this->value);
            $builder->or_where("{$this->activity_alias}.name", 'ILIKE', $this->value);
        });
    }
}