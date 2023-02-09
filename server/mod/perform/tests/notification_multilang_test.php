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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\activity\active;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\testing\activity_generator_configuration;
use totara_core\relationship\relationship;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_multilang_testcase extends mod_perform_notification_testcase {

    use \core_phpunit\language_pack_faker_trait;

    public function test_send_notifications_in_language_of_receiver() {
        global $CFG, $USER;

        // TODO - revisit this test after (TL-35055) centralised notifications multi-lang work is finished
        $this->markTestSkipped();

        $languages = ['de', 'fr', 'es', 'da'];

        foreach ($languages as $language) {
            $prefix = strtoupper($language);

            $this->add_fake_language_pack(
                $language,
                [
                    'langconfig' => [
                        'strftimedate' => $prefix.' strftime %A',
                    ],
                    'mod_perform' => [
                        'system_activity_type:appraisal' => $prefix.' Appraisal',
                        'user_activities_select_participants_page_title' => $prefix.' Select participants',
                        'conditional_duedate_participant_placeholder' => $prefix.' conditional duedate participant {$a->duedate}',
                        'conditional_duedate_subject_placeholder' => $prefix.' conditional duedate subject {$a->duedate}',
                        'template_instance_created_subject_body' => $prefix.' instance for subject created body {$a->recipient_fullname} {$a->activity_name} {$a->activity_type} {$a->activity_link} {$a->conditional_duedate}',
                        'template_instance_created_subject_subject' => $prefix.' instance for subject created subject {$a->activity_name} {$a->activity_type}',
                        'template_instance_created_manager_body' => $prefix.' instance for manager created body {$a->recipient_fullname} {$a->activity_name} {$a->activity_type} {$a->activity_link} {$a->conditional_duedate}',
                        'template_instance_created_manager_subject' => $prefix.' instance for manager created subject {$a->activity_name} {$a->activity_type}',
                        'template_instance_created_perform_external_body' => $prefix.' instance for external created body {$a->recipient_fullname} {$a->activity_name} {$a->activity_type} {$a->activity_link} {$a->conditional_duedate}',
                        'template_instance_created_perform_external_subject' => $prefix.' instance for external created subject {$a->activity_name} {$a->activity_type}',
                        'template_participant_selection_subject_body' => $prefix.' select participants body {$a->activity_name} {$a->activity_type}',
                        'template_participant_selection_subject_subject' => $prefix.' select participants subject {$a->activity_name} {$a->activity_type}',
                    ]
                ]
            );
        }

        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        /**
         * Language setup:
         *
         * System: da
         * Subject: fr
         * Manager: es
         */
        $CFG->lang = 'da';

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');

        $sink = $this->redirectMessages();

        $this->setAdminUser();
        // Set current language to something unrelated to make sure it does not influence the lang of the notifications
        $USER->lang = 'de';
        $this->assertEquals('de', current_language());

        $configuration = activity_generator_configuration::new()
            ->set_activity_status(active::get_code())
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity(1)
            ->set_number_of_elements_per_section(1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->enable_manager_for_each_subject_user()
            ->enable_multilang_filter()
            ->set_language_per_relationship([
                constants::RELATIONSHIP_SUBJECT => 'fr',
                constants::RELATIONSHIP_MANAGER => 'es',
            ])
            ->set_relationships_per_section([
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_EXTERNAL
            ]);

        $activities = $perform_generator->create_full_activities($configuration);

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $message = array_shift($messages);

        $subject_instance = subject_instance_model::load_by_entity(subject_instance::repository()->one());

        $this->assertEquals($subject_instance->subject_user_id, $message->useridto);
        $this->assertStringContainsString('FR select participants subject', $message->subject);
        $this->assertStringContainsString('FR select participants body', $message->fullmessage);
        $this->assert_message_contains_only_language($message, 'FR');

        $sink->clear();

        // Make sure the progress records are there
        (new manual_participant_progress())->generate();

        foreach ($activities as $activity) {
            $perform_generator->create_manual_users_for_activity($activity, [constants::RELATIONSHIP_EXTERNAL]);
        }

        self::executeAdhocTasks();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);

        $subject_participant_instance = participant_instance_model::load_by_entity(
            participant_instance::repository()
                ->where('core_relationship_id', $subject_relationship->id)
                ->where('subject_instance_id', $subject_instance->id)
                ->one()
        );
        $manager_participant_instance = participant_instance_model::load_by_entity(
            participant_instance::repository()
                ->where('core_relationship_id', $manager_relationship->id)
                ->where('subject_instance_id', $subject_instance->id)
                ->one()
        );

        $messages = $sink->get_messages();
        $this->assertCount(3, $messages);

        foreach ($messages as $message) {
            if ($subject_participant_instance->is_for_user($message->useridto)) {
                $this->assertStringContainsString('FR instance for subject created subject', $message->subject);
                $this->assert_message_contains_only_language($message, 'FR');
            } else if ($manager_participant_instance->is_for_user($message->useridto)) {
                $this->assertStringContainsString('ES instance for manager created subject', $message->subject);
                $this->assert_message_contains_only_language($message, 'ES');
            } else {
                $this->assertStringContainsString('DA instance for external created subject', $message->subject);
                $this->assert_message_contains_only_language($message, 'DA');
            }
        }

        // Make sure the language is set back to previous value
        $this->assertEquals('de', current_language());
    }

    private function assert_message_contains_only_language(stdClass $message, string $expected_language): void {
        $expected_language = strtoupper($expected_language);
        $existing_languages = ['DE', 'FR', 'ES', 'DA'];

        foreach ($existing_languages as $existing_language) {
            if ($expected_language == $existing_language) {
                $this->assertStringContainsString($existing_language, $message->fullmessage);
                $this->assertStringContainsString($existing_language, $message->subject);
            } else {
                $this->assertStringNotContainsString($existing_language, $message->fullmessage);
                $this->assertStringNotContainsString($existing_language, $message->subject);
            }
        }
    }

}
