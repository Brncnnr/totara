<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use core\orm\entity\filter\filter;

class subject_instances_activity_type extends filter {

    /**
     * @var string
     */
    protected $activity_alias;

    public function __construct(string $activity_alias = 'a') {
        parent::__construct([]);
        $this->activity_alias = $activity_alias;
    }

    public function apply(): void {
        $this->builder->where("{$this->activity_alias}.type_id", $this->value);
    }
}