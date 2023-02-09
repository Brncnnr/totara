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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\entity;

use core\orm\entity\repository;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_source;
use performelement_linked_review\linked_review;

class linked_review_repository extends repository {

    /**
     * Determine whether the viewing user is a selecting participant in a linked_review element of the target user
     *
     * @param string $content_type Type of content to check
     * @param int $viewing_user_id The user requesting to view the target user
     * @param int $target_user_id The user who's
     * @return bool
     */
    public static function user_is_selecting_participant(string $content_type, int $viewing_user_id, int $target_user_id): bool {
        global $DB;

        /** @var bool $exists */
        $exists = false;

        // Get all linked review elements of all instances of the target user the viewing user participates in
        $elements = element_entity::repository()
            ->as('e')
            ->select_raw('e.id AS element_id, e.data, pi.core_relationship_id')
            ->join([section_element::TABLE, 'se'], 'se.element_id', 'e.id')
            ->join([participant_section::TABLE, 'ps'], 'se.section_id', 'ps.section_id')
            ->join([participant_instance::TABLE, 'pi'], 'ps.participant_instance_id', 'pi.id')
            ->join([subject_instance::TABLE, 'si'], 'pi.subject_instance_id', 'si.id')
            ->where('pi.participant_source', participant_source::INTERNAL)
            ->where('pi.participant_id', $viewing_user_id)
            ->where('e.plugin_name', linked_review::get_plugin_name())
            ->where('si.subject_user_id', $target_user_id)
            ->get(true);

        foreach ($elements as $element) {
            $data = json_decode($element->data, true);

            // Only continue if it's a review element of the type we are interested in
            if ($data['content_type'] != $content_type) {
                continue;
            }

            if (in_array($element->core_relationship_id, $data['selection_relationships'])) {
                $exists = true;
                break;
            }
        }

        return $exists;
    }

}