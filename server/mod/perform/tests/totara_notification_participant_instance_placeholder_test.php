<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user as user_entity;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\controllers\activity\user_activities;
use mod_perform\controllers\activity\user_activities_select_participants;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\notification\factory;
use mod_perform\state\activity\active;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator;
use mod_perform\totara_notification\placeholder\participant_instance as participant_instance_placeholder;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_perform
 * @group totara_notification
 */
class mod_perform_totara_notification_participant_instance_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }

    public function test_placeholder() {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, participant_instance_placeholder::get_options());
        self::assertEqualsCanonicalizing(
            [
                'relationship',
                'participant_full_name',
                'activity_name_link',
                'days_active'
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        /** @var mod_perform\models\activity\participant_instance $participant_instance */
        [$participant_instance, $activity] = $this->create_data();
        $placeholder = participant_instance_placeholder::from_id($participant_instance->id);

        self::assertEquals('Subject', $placeholder->do_get('relationship'));
        self::assertEquals('Uma Thurman', $placeholder->do_get('participant_full_name'));

        $url = new moodle_url(user_activities::get_base_url());
        $activity_name_link = html_writer::link($url, format_string($activity->name));
        self::assertEquals($activity_name_link, $placeholder->do_get('activity_name_link'));

        self::assertEquals(0, $placeholder->do_get('days_active'));
    }

    public function test_placeholder_for_external_respondent() {
        self::markTestSkipped('Activate and adjust when TL-34958 is done.');

        $data = $this->set_up_activity_with_external_relationship();

        $placeholder = participant_instance_placeholder::from_id($data['subject_participant_instance']->id);

        self::assertEquals('Subject', $placeholder->do_get('relationship'));
        self::assertEquals('Subject One', $placeholder->do_get('participant_full_name'));

        $url = new moodle_url(user_activities::get_base_url());
        $activity_name_link = html_writer::link($url, 'Example activity');
        self::assertEquals($activity_name_link, $placeholder->do_get('activity_name_link'));

        /** @var external_participant $external_participant1_entity */
        $external_participant1_entity = $data['external_participant1'];

        $placeholder->set_recipient_id(-1 * $external_participant1_entity->id);

        $external_participant_instance1_model = participant_instance::load_by_entity($data['external_participant_instance1']);
        $url = (string)$external_participant_instance1_model->get_participation_url();
        $activity_name_link = html_writer::link($url, 'Example activity');

        self::assertNotEmpty($external_participant1_entity->token);

        self::assertStringContainsString($external_participant1_entity->token, $activity_name_link);
        self::assertEquals($activity_name_link, $placeholder->do_get('activity_name_link'));
    }

    /**
     * Create activity and participant instances required for testing.
     *
     * @return array
     */
    private function create_data(): array {
        self::setAdminUser();

        $generator = generator::instance();
        $activity = $generator->create_activity_in_container(
            [
                'activity_name' => 'Lorem ipsum dolor sit amet consectetuer adipiscing elit',
                'activity_type' => 'feedback'
            ]
        );
        $activity->settings->update([activity_setting::CLOSE_ON_COMPLETION => true]);
        $section = $activity->get_sections()->first();

        $user = self::getDataGenerator()->create_user(['firstname' => 'Uma', 'lastname' => 'Thurman']);

        $due_date = factory::create_clock()->get_time() + (5 * DAYSECS);

        $subject_instance = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $user->id,
            'due_date' => $due_date,
        ]);

        $subject_relationship_id = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;

        $participant1_section_entity = $generator->create_participant_instance_and_section(
            $activity,
            $user,
            $subject_instance->id,
            $section,
            $subject_relationship_id
        );

        $participant_entity = $participant1_section_entity->participant_instance;
        $participant_instance = participant_instance::load_by_entity($participant_entity);

        return [$participant_instance, $activity];
    }

    private function set_up_activity_with_external_relationship(): array {
        $this->setAdminUser();
        $generator = generator::instance();

        // Create an activity with subject and external respondent.
        $configuration = activity_generator_configuration::new()
            ->set_activity_status(active::get_code())
            ->set_number_of_users_per_user_group_type(2)
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_EXTERNAL,
                    constants::RELATIONSHIP_SUBJECT,
                ]
            );
        $activity = $generator->create_full_activities($configuration)->first();

        // Make sure the progress records are there and add the external users.
        (new manual_participant_progress())->generate();
        $generator->create_manual_users_for_activity($activity, [constants::RELATIONSHIP_EXTERNAL], 2);

        // Verify generated data is as expected.
        self::assertEquals(6, participant_instance_entity::repository()->count());
        $subject_instances = subject_instance::repository()->order_by('id');
        self::assertEquals(2, $subject_instances->count());

        /** @var subject_instance $subject_instance1 */
        $subject_instance1 = $subject_instances->first();

        // Get the external participant instances for the subject instance we picked.
        $external_participant_instances = participant_instance_entity::repository()
            ->where('subject_instance_id', $subject_instance1->id)
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->get();
        self::assertEquals(2, $external_participant_instances->count());

        /** @var participant_instance $external_participant_instance1 */
        $external_participant_instance1 = $external_participant_instances->first();
        /** @var participant_instance $external_participant_instance2 */
        $external_participant_instance2 = $external_participant_instances->last();

        /** @var external_participant $external_participant1 */
        $external_participant1 = external_participant::repository()->find($external_participant_instance1->participant_id);
        /** @var external_participant $external_participant2 */
        $external_participant2 = external_participant::repository()->find($external_participant_instance2->participant_id);

        $subject_id = $subject_instance1->subject_user_id;

        $subject_participant_instance = participant_instance_entity::repository()
            ->where('subject_instance_id', $subject_instance1->id)
            ->where('participant_source', participant_source::INTERNAL)
            ->where('participant_id', $subject_id)
            ->one(true);

        user_entity::repository()
            ->where('id', $subject_id)
            ->update(['firstname' => 'Subject', 'lastname' => 'One']);

        activity::repository()
            ->where('id', $activity->id)
            ->update(['name' => 'Example activity', 'type_id' => 1]);

        return [
            'subject_participant_instance' => $subject_participant_instance,
            'external_participant_instance1' => $external_participant_instance1,
            'subject_instance_id' => $subject_instance1->id,
            'subject_id' => $subject_id,
            'external_participant1' => $external_participant1,
            'external_participant1_email' => $external_participant1->email,
            'external_participant2_email' => $external_participant2->email,
            'external_participant1_name' => $external_participant1->name,
        ];
    }
}
