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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

use mod_perform\constants;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance;

global $CFG;
require_once($CFG->dirroot . '/mod/perform/tests/weka_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_long_text_weka_response_upgrade_testcase extends mod_perform_weka_testcase {

    public function test_plain_text_responses_are_upgraded_to_weka(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');
        $this->setAdminUser();
        $user = get_admin();

        // Create some activity data.
        $generator = $this->perform_generator();
        $activity = $generator->create_activity_in_container();
        $section = $generator->create_section($activity);
        $subject_instance = $generator->create_subject_instance([
            'activity_id' => $activity->id, 'subject_username' => $user->username, 'subject_is_participating' => true,
        ]);
        $participant_instance = participant_instance::repository()->order_by('id')->first();

        // Non long text elements shouldn't be affected
        $short_text_response_data = json_encode('ABC');
        $short_text_element = $generator->create_element(['plugin_name' => 'short_text']);
        $short_text_section_element = $generator->create_section_element($section, $short_text_element);
        $short_text_response = new element_response();
        $short_text_response->participant_instance_id = $participant_instance->id;
        $short_text_response->section_element_id = $short_text_section_element->id;
        $short_text_response->response_data = $short_text_response_data;
        $short_text_response->save();

        $long_text_element = $generator->create_element(['plugin_name' => 'long_text']);
        $long_text_section_element = $generator->create_section_element($section, $long_text_element);

        // Element response data scenarios
        $response_scenarios = [
            // Newline characters should be turned into paragraph breaks
            [
                'input' => json_encode("One\nTwo\n\nThree"),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"One"},{"type":"hard_break"},{"type":"text","text":"Two"},{"type":"hard_break"},{"type":"hard_break"},{"type":"text","text":"Three"}]}]}',
                'entity' => new element_response(),
            ],
            // Slashes (e.g. in URLs) should not be escaped
            [
                'input' => json_encode("Hi! http://www.example.com/interesting-photo.jpg is an interesting photo"),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Hi! http://www.example.com/interesting-photo.jpg is an interesting photo"}]}]}',
                'entity' => new element_response(),
            ],
            // Nothing unusual should happen if you put valid JSON inside JSON
            [
                'input' => json_encode('{"valid":"JSON"}'),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"{\"valid\":\"JSON\"}"}]}]}',
                'entity' => new element_response(),
            ],
            // Nothing unusual should happen if you put invalid JSON inside JSON
            [
                'input' => json_encode('{"bad json":}'),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"{\"bad json\":}"}]}]}',
                'entity' => new element_response(),
            ],
            // A response with only whitespace should have that whitespace inserted in a text node
            [
                'input' => json_encode("      "),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"      "}]}]}',
                'entity' => new element_response(),
            ],
            // An empty response (submitted by the user) should still have a text node
            [
                'input' => json_encode(""),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":""}]}]}',
                'entity' => new element_response(),
            ],
            // An empty response (that isn't submitted by the user) should get converted to null
            [
                'input' => "",
                'expected_output' => null,
                'entity' => new element_response(),
            ],
            // Null should stay null
            [
                'input' => null,
                'expected_output' => null,
                'entity' => new element_response(),
            ],
            // The text "null" entered in by a user should display as normal
            [
                'input' => json_encode('null'),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"null"}]}]}',
                'entity' => new element_response(),
            ],
            // If "null" is not encoded as JSON, then it wasn't entered by the user and should be turned into an actual null value
            [
                'input' => 'null',
                'expected_output' => null,
                'entity' => new element_response(),
            ],
        ];
        if ($DB->get_dbfamily() === 'postgres') {
            // Unicode should not be escaped.
            // Note that we can really only trust running this on postgres,
            // because it supports extended unicode without collation by default.
            $unicode = iconv("UTF-16BE", "UTF-8", hex2bin('d83ddc4d')); // Thumbs up emoji
            $response_scenarios[] = [
                'input' => json_encode($unicode),
                'expected_output' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"' . $unicode . '"}]}]}',
                'entity' => new element_response(),
            ];
        }

        foreach ($response_scenarios as $response) {
            $participant_instance = $generator->create_participant_instance(
                $user, $subject_instance->id, constants::RELATIONSHIP_SUBJECT
            );

            $response['entity']->participant_instance_id = $participant_instance->id;
            $response['entity']->section_element_id = $long_text_section_element->id;
            $response['entity']->response_data = $response['input'];
            $response['entity']->save();
        }

        // Run the upgrade and make sure the result what we expect.
        mod_perform_upgrade_long_text_responses_to_weka_format();
        foreach ($response_scenarios as $response) {
            $response['entity']->refresh();
            $this->assertEquals($response['expected_output'], $response['entity']->response_data);
        }
        // Non long text element shouldn't have been affected
        $this->assertEquals($short_text_response_data, $short_text_response->refresh()->response_data);

        // Run the upgrade again - the upgrade should be idempotent and can be run again without reformatting it again.
        mod_perform_upgrade_long_text_responses_to_weka_format();
        foreach ($response_scenarios as $response) {
            $response['entity']->refresh();
            $this->assertEquals($response['expected_output'], $response['entity']->response_data);
        }
        // Non long text element shouldn't have been affected
        $this->assertEquals($short_text_response_data, $short_text_response->refresh()->response_data);
    }

}
