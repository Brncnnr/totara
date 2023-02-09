<?php

use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\models\activity\subject_instance;
use mod_perform\totara_notification\recipient\participant_selector_subject as recipient_group;
use totara_core\relationship\relationship;

defined('MOODLE_INTERNAL') || die();

class mod_perform_totara_notification_recipient_participant_selector_subject_test extends testcase {
    /**
     * Test the function fails with invalid args.
     */
    public function test_missing_args(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Missing activity_id");

        recipient_group::get_user_ids(['subject_user_id' => 1]);
    }

    /**
     * Test the function returns the given input.
     */
    public function test_result(): void {
        $this->setAdminUser();

        $data = $this->create_test_data();

        $user_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity1->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance->id]);
        $this->assertEquals([$data->user->id], $user_ids);
    }

    /**
     * Create test data.
     */
    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();
        $user = $this->getDataGenerator()->create_user();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);

        $activity = $perform_generator->create_activity_in_container();
        $perform_generator->create_manual_relationships_for_activity($activity, [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $subject_relationship->id, 'manual' => $mentor_relationship->id],
        ]);

        $subject_instance_entity = $perform_generator->create_subject_instance_with_pending_selections(
            $activity, $user, [$peer_relationship, $mentor_relationship]
        );

        $subject_instance = subject_instance::load_by_entity($subject_instance_entity);

        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Mid year performance']);
        $data->activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'End year performance']);
        $data->subject_instance = $subject_instance;
        $data->user = $user;

        return $data;
    }

}