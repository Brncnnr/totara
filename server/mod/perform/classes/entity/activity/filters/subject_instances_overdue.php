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
use mod_perform\state\subject_instance\complete as subject_instance_complete;

class subject_instances_overdue extends filter {

    /**
     * @var string
     */
    protected $subject_instance_alias;

    public function __construct(string $subject_instance_alias = 'si') {
        parent::__construct([]);
        $this->subject_instance_alias = $subject_instance_alias;
    }

    public function apply(): void {
        // Don't apply any extra filter when set to false.
        // We want to see overdue as well as not overdue when this filter isn't active.
        if ($this->value) {
            // If the due date falls within today, it is technically not overdue
            // until 00:00:00 tommorrow - in the user's timezone.
            $beginning_of_today = usergetmidnight(time());

            $this->builder->where_not_null("{$this->subject_instance_alias}.due_date");
            $this->builder->where("{$this->subject_instance_alias}.progress", '!=', subject_instance_complete::get_code());
            $this->builder->where("{$this->subject_instance_alias}.due_date", '<', $beginning_of_today);
        }
    }
}