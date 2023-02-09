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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use mod_perform\models\activity\element;
use stdClass;

/**
 * This helper can be implemented by sub plugins if need extra processing steps when cloning
 *
 * @package mod_perform\models\activity\helpers
 */
interface element_clone_helper {

    /**
     * Extra processing steps for element cloning
     *
     * @param int $new_section_element_id newly created section element id
     * @param stdClass $data newly created section id and element id
     * @param element $element newly create element
     */
    public function restore(int $new_section_element_id, stdClass $data, element $element): void;

}
