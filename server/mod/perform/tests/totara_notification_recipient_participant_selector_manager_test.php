<?php

use mod_perform\constants;
use mod_perform\models\activity\subject_instance;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\totara_notification\recipient\participant_selector_manager as recipient_group;
use totara_core\relationship\relationship;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/participant_instance_sync_testcase.php');

class mod_perform_totara_notification_recipient_participant_selector_manager_test extends mod_perform_participant_instance_sync_testcase {
    /**
     * Test the function fails with invalid args.
     */
    public function test_missing_args(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Missing activity_id");

        recipient_group::get_user_ids(['subject_user_id' => 1]);
    }

    /**
     * Test the function manager not in selection participants.
     */
    public function test_manager_in_selection_participants(): void {
        $this->setAdminUser();

        $data = $this->create_test_data();

        // No manager.
        $manager_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity1->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance_activity1->id
        ]);
        $this->assertEquals([], $manager_ids);

        // Add manager.
        [$manager1] = $this->add_manager_for_user($data->user->id);
        $progress_service = new manual_participant_progress();
        $progress_service->generate();

        $manager_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity3->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance_activity3->id
        ]);
        $this->assertEquals($manager1->id, array_values($manager_ids)[0]);

        // Add second and third manager
        $this->add_manager_for_user($data->user->id);
        $this->add_manager_for_user($data->user->id);
        $progress_service = new manual_participant_progress();
        $progress_service->generate();

        $manager_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity3->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance_activity3->id
        ]);
        $this->assertEquals(3, count($manager_ids));
    }

    /**
     * Create test data.
     */
    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();
        $user = $this->getDataGenerator()->create_user();
        $data->user = $user;

        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Mid year performance']);
        $data->activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'End year performance']);
        $data->activity3 = $perform_generator->create_activity_in_container(['activity_name' => 'Second year performance']);


        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);

        $perform_generator->create_manual_relationships_for_activity($data->activity3 , [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $manager_relationship->id, 'manual' => $mentor_relationship->id],
        ]);

        $subject_instance_entity_activity1 = $perform_generator->create_subject_instance_with_pending_selections(
            $data->activity1, $user, [$peer_relationship, $mentor_relationship]
        );
        $subject_instance_activity1 = subject_instance::load_by_entity($subject_instance_entity_activity1);
        $data->subject_instance_activity1 = $subject_instance_activity1;

        $subject_instance_entity_activity2 = $perform_generator->create_subject_instance_with_pending_selections(
            $data->activity2, $user, [$peer_relationship, $mentor_relationship]
        );
        $subject_instance_activity2 = subject_instance::load_by_entity($subject_instance_entity_activity2);
        $data->subject_instance_activity2 = $subject_instance_activity2;

        $subject_instance_entity_activity3 = $perform_generator->create_subject_instance_with_pending_selections(
            $data->activity3, $user, [$peer_relationship, $mentor_relationship]
        );
        $subject_instance_activity3 = subject_instance::load_by_entity($subject_instance_entity_activity3);
        $data->subject_instance_activity3 = $subject_instance_activity3;

        return $data;
    }

}