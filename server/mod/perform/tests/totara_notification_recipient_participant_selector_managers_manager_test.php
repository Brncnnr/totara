<?php

use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\models\activity\subject_instance;
use mod_perform\totara_notification\recipient\participant_selector_managers_manager as recipient_group;
use totara_job\job_assignment;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_core\relationship\relationship;

defined('MOODLE_INTERNAL') || die();

class mod_perform_totara_notification_recipient_participant_selector_managers_manager_test extends testcase {
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
    public function test_managers_manager_in_selection_participants(): void {
        $this->setAdminUser();

        $data = $this->create_test_data();

        // Managers_manager is not in default selection of participants.
        $managers_manager_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity1->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance_activity1->id
        ]);
        $this->assertEquals([], $managers_manager_ids);

        // Managers_manager is in default selection of participants.
        $managers_manager_ids = recipient_group::get_user_ids([
            'activity_id' => $data->activity3->id,
            'subject_user_id' => $data->user->id,
            'subject_instance_id' => $data->subject_instance_activity3->id
        ]);
        $this->assertEquals($data->managers_manager->id, array_values($managers_manager_ids)[0]);
    }

    /**
     * Create test data.
     */
    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();
        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $managers_manager = $this->getDataGenerator()->create_user();

        // Job assignments
        /** @var job_assignment $subject1_ja2 */
        $user_ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'app/no_cohort/' . $user->id,
            'managerjaid' => job_assignment::create_default($manager->id)->id,
        ]);

        job_assignment_entity::repository()
            ->where('id', $user_ja->managerjaid)
            ->update(['managerjaid' => job_assignment::create_default($managers_manager->id)->id]);


        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Mid year performance']);
        $data->activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'End year performance']);
        $data->activity3 = $perform_generator->create_activity_in_container(['activity_name' => 'Second year performance']);
        $data->user = $user;
        $data->managers_manager = $managers_manager;

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $managers_manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGERS_MANAGER);

        $perform_generator->create_manual_relationships_for_activity($data->activity3 , [
            ['selector' => $subject_relationship->id, 'manual' => $peer_relationship->id],
            ['selector' => $managers_manager_relationship->id, 'manual' => $mentor_relationship->id],
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