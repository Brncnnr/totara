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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

use core\collection;
use core\json_editor\helper\document_helper;
use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\section_element_response;
use performelement_long_text\long_text;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/mod/perform/tests/weka_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_long_text_draft_files_testcase extends mod_perform_weka_testcase {

    use webapi_phpunit_helper;

    public function test_post_create_update(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user();
        $user_context = context_user::instance($user->id);
        $relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);

        // Generate some data.
        $activity = $this->perform_generator()->create_activity_in_container();
        $section = $this->perform_generator()->create_section($activity);
        $subject_instance = $this->perform_generator()->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $user->id,
        ]);
        $participant_section = $this->perform_generator()->create_participant_instance_and_section(
            $activity, $user, $subject_instance->id, $section, $relationship->id
        );
        $participant_section = participant_section::load_by_entity($participant_section);
        $element = element::create($activity->get_context(), 'long_text', 'test element 1 title');
        $section_element = section_element::create($section, $element, 123);
        $response = new section_element_response(
            $participant_section->participant_instance,
            $section_element,
            null,
            new collection()
        );

        self::setUser($user);

        // Get a draft ID from the mutation
        $draft_id = $this->resolve_graphql_query('performelement_long_text_get_draft_id', [
            'section_element_id' => $section_element->id,
            'participant_instance_id' => $participant_section->participant_instance_id,
        ]);

        $this->assertIsInt($draft_id);
        $this->assertGreaterThan(0, $draft_id);

        // Create response
        $weka_data = $this->create_weka_document_with_file($draft_id, $user_context, false);
        $response_data = document_helper::json_encode_document([
            'draft_id' => $draft_id,
            'weka' => $weka_data,
        ]);
        $response->set_response_data($response_data);
        $response->save();

        // Confirm that the file URL has been rewritten.
        $this->assertStringContainsString('@@PLUGINFILE@@/test_file.png', $response->get_response_data());

        // The file should exist in the user's draft area.
        // Note that on a real site the draft file would be deleted shortly via cron after it is moved to the long_text area.
        $this->assertTrue(get_file_storage()->file_exists(
            $user_context->id, 'user', 'draft', $draft_id, '/', 'test_file.png'
        ));
        // The file should now permanently exist in the long_text component response file area.
        $this->assertTrue(get_file_storage()->file_exists(
            $activity->get_context_id(),
            long_text::get_response_files_component_name(),
            long_text::get_response_files_filearea_name(),
            $response->id,
            '/',
            'test_file.png'
        ));
    }

}
