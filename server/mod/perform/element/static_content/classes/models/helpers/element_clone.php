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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_static_content
 */

namespace performelement_static_content\models\helpers;

use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_cloning;
use performelement_static_content\static_content;
use stdClass;

class element_clone implements element_cloning {

    /**
     * @inheritDoc
     */
    public function create(int $activity_id, stdClass $element): int {
        global $DB;

        // To prevent problem with context
        $old_element = element::load_by_id($element->id);
        $data = json_decode($old_element->data, true);

        // Create new draft area
        $data['draftId'] = 0;
        unset($data['element_id']);

        if (isset($data['wekaDoc'])) {
            // Prepare draft area for the element being cloned.
            // This will create a draftId and we will use the
            // new draftId for the new element.
            $data['wekaDoc'] = file_prepare_draft_area(
                $data['draftId'],
                $old_element->context_id,
                'performelement_static_content',
                'content',
                $old_element->id,
                null,
                $data['wekaDoc']
            );
        }

        // Insert the record into the database.
        $element->data = json_encode($data);
        $id = $DB->insert_record('perform_element', $element);

        // Stitch up the new element.
        $static_content = static_content::load_by_plugin('static_content');
        $static_content->post_create(element::load_by_id($id));

        return $id;
    }
}