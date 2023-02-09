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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use coding_exception;
use core\orm\entity\filter\filter;

class subject_instance_id extends filter {

    /**
     * @var string
     */
    protected $subject_instance_alias;

    public function __construct(string $subject_instance_alias = 'si') {
        parent::__construct([]);
        $this->subject_instance_alias = $subject_instance_alias;
    }

    public function apply(): void {
        if (!is_array($this->value)) {
            throw new coding_exception('subject instance id filter but have an array for value');
        }

        if (count($this->value) > 0) {
            $this->builder->where_in("{$this->subject_instance_alias}.id", $this->value);
        }
    }
}