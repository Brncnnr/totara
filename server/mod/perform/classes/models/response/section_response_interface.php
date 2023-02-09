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

namespace mod_perform\models\response;

use core\collection;
use mod_perform\models\activity\section;

/**
 * @property-read int $section_id
 * @property-read int $progress
 * @property-read int $created_at
 * @property-read int $updated_at
 * @property-read string $progress_status
 * @property-read section $section
 * @property-read section_element_response[]|collection $section_element_responses
 */
interface section_response_interface {

    public function get_section_id(): int;

    public function get_section(): section;

    public function get_section_element_responses(): collection;

}