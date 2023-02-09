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
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\participant_section;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/mod/perform/tests/weka_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_long_text_webapi_resolver_mutation_prepare_draft_area_testcase extends mod_perform_weka_testcase {

    private const MUTATION = 'performelement_long_text_prepare_draft_area';

    use webapi_phpunit_helper;

    public function test_ajax_mutation_successful(): void {
        [$participant_user, $participant_instance_id, $section_element_id] = $this->create_data();
        self::setUser($participant_user);

        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'section_element_id' => $section_element_id,
            'participant_instance_id' => $participant_instance_id,
        ]);
        $this->assertDebuggingCalled('The performelement_long_text_prepare_draft_area mutation has been deprecated, please use the performelement_long_text_get_draft_id query instead', DEBUG_DEVELOPER);
        $this->assertNotNull($result);
        $this->assertGreaterThan(1, $result);
    }

    public function test_ajax_mutation_fails_when_not_logged_in(): void {
        [, $participant_instance_id, $section_element_id] = $this->create_data();
        self::setUser();

        $this->expectException(require_login_exception::class);

        $this->resolve_graphql_mutation(self::MUTATION, [
            'section_element_id' => $section_element_id,
            'participant_instance_id' => $participant_instance_id,
        ]);
        // No debugging expected because exception is thrown before debugging call.
    }

    public function test_ajax_mutation_fails_when_feature_disabled(): void {
        [$participant_user, $participant_instance_id, $section_element_id] = $this->create_data();
        self::setUser($participant_user);

        advanced_feature::disable('performance_activities');
        $this->expectException(feature_not_available_exception::class);

        $this->resolve_graphql_mutation(self::MUTATION, [
            'section_element_id' => $section_element_id,
            'participant_instance_id' => $participant_instance_id,
        ]);
        // No debugging expected because exception is thrown before debugging call.
    }

    public function test_ajax_mutation_fails_when_ids_are_invalid(): void {
        self::setAdminUser();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation(self::MUTATION, [
            'section_element_id' => -1,
            'participant_instance_id' => -1,
        ]);
        // No debugging expected because exception is thrown before debugging call.
    }

    private function create_data(): array {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user();
        $relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);

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

        return [$user, $participant_section->participant_instance_id, $section_element->id];
    }

}
