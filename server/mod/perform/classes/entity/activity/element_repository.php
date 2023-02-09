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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\repository;
use core\orm\query\builder;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;

class element_repository extends repository {

    /**
     * Get all the user ids of subjects using a particular element.
     *
     * @deprecated since Totara 16
     * @param int $element_id
     * @return array
     */
    public function get_subject_user_ids_using_element(int $element_id): array {
        debugging('\mod_perform\entity\activity\element_repository::get_subject_user_ids_using_element() is deprecated.', DEBUG_DEVELOPER);

        return builder::table(section_element::TABLE, 'se')
            ->select_raw('distinct si.subject_user_id as subject_user_id')
            ->join([section::TABLE, 's'], 's.id', 'se.section_id')
            ->join([participant_section::TABLE, 'ps'], 'ps.section_id', 's.id')
            ->join([participant_instance::TABLE, 'pi'], 'pi.id', 'ps.participant_instance_id')
            ->join([subject_instance_entity::TABLE, 'si'], 'si.id', 'pi.subject_instance_id')
            ->where('se.element_id', $element_id)
            ->get()
            ->pluck('subject_user_id');
    }
}