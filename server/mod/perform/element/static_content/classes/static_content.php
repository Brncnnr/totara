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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package performelement_static_content
 */

namespace performelement_static_content;

use mod_perform\entity\activity\element;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\element as element_model;
use performelement_static_content\local\helper;
use stdClass;

class static_content extends element_plugin {

    /**
     * This method return element's user form vue component name
     * @return string
     * @deprecated since Totara 13.2
     */
    public function get_participant_response_component(): string {
        debugging(
            '\performelement_static_content\static_content::get_participant_response_component() is deprecated and should no longer be used.'
            . 'Only classes expending \mod_perform\models\activity\respondable_element_plugin should implement this method',
            DEBUG_DEVELOPER
        );
        return $this->get_component_path('ElementParticipant');
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function get_group(): int {
        return self::GROUP_OTHER;
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 100;
    }

    /**
     * We need to check if the user uploaded some files and get those
     * into permanent storage.
     *
     * @param element_model $element
     */
    public function post_create(element_model $element): void {
        global $CFG;

        $data = json_decode($element->data ?? '', true);

        if (isset($data['draftId']) && defined($data['docFormat'])) {
            $context = $element->get_context();
            // Start processing the files within the content.
            require_once("{$CFG->dirroot}/lib/filelib.php");
            $options = helper::get_editor_options($context);

            // Simulate the form data.
            $editordata = new stdClass();
            $editordata->content_editor = [
                'text' => $data['wekaDoc'],
                'format' => (int) constant($data['docFormat']),
                'itemid' => $data['draftId']
            ];

            // Prepare the content of the 'editor' form element
            // with embedded media files to be saved in database
            $editordata = file_postupdate_standard_editor(
                $editordata,
                'content',
                $options,
                $context,
                'performelement_static_content',
                'content',
                $element->id
            );

            $data['wekaDoc'] = $editordata->content;
            $data['element_id'] = $element->id;
            // Draft files are temporary, so we don't need store them
            unset($data['draftId']);
            $data = json_encode($data);

            // Update the element data with the updated content.
            element::repository()
                ->where('id', $element->id)
                ->update([
                    'data' => $data,
                ]);
        }
    }

    /**
     * We need to check if the user uploaded some files and get those
     * into permanent storage.
     *
     * @param element_model $element
     */
    public function post_update(element_model $element): void {
        // Follow same process as post_create.
        $this->post_create($element);
    }

    /**
     * @inheritDoc
     */
    public function has_title(): bool {
        return true;
    }

    /**
     * @return string
     */
    public function get_title_text():string {
        return get_string('title', 'performelement_static_content');
    }

    /**
     * @inheritDoc
     */
    public function is_title_required(): bool {
        return false;
    }

}
