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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use container_perform\perform;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\expand_task;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\models\activity\track;
use mod_perform\models\activity\trigger\repeating\after_creation;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\user_groups\grouping;
use totara_job\job_assignment;

/**
 * @group perform
 */
class mod_perform_subject_instance_performance_reporting_testcase extends testcase {

    public function test_report_data_with_repeating_subject_instances() {
        self::setAdminUser();

        $generator = \mod_perform\testing\generator::instance();
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->disable_subject_instances()
            ->enable_appraiser_for_each_subject_user()
            ->enable_manager_for_each_subject_user()
            ->set_relationships_per_section([
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_APPRAISER
            ])
            ->set_number_of_users_per_user_group_type(2);
        /** @var activity $activity */
        $activity = $generator->create_full_activities($config)->first();
        /** @var track $track */
        $track = $activity->get_tracks()->first();

        // Set repeat to one day after creation.
        $offset = new date_offset(1, date_offset::UNIT_DAY);
        $track->set_repeating_enabled(
            track_entity::SCHEDULE_REPEATING_TYPE_UNSET,
            $offset,
            null,
            new after_creation()
        );
        $track->update();

        // Create initial instances.
        (new subject_instance_creation())->generate_instances();
        $subject_instances = subject_instance::repository()->get()->all();
        self::assertCount(2, $subject_instances);

        /** @var subject_instance $subject_instance_1 */
        $subject_instance_1 = $subject_instances[0];

        // Have a repeating instance created by manipulating created_date.
        $subject_instance_1->created_at = time() - (2 * 86400);
        $subject_instance_1->update();
        (new subject_instance_creation())->generate_instances();
        self::assertCount(3, subject_instance::repository()->get());

        // Pick the subject user with the repeating instance.
        $subject_user_1_id = $subject_instance_1->subject_user_id;
        $subject_instances_user_1 = subject_instance::repository()
            ->where('subject_user_id', $subject_user_1_id)
            ->get();
        self::assertCount(2, $subject_instances_user_1);

        // Set up report.
        $config = new rb_config();
        $config->set_embeddata(['subject_user_id' => $subject_user_1_id]);
        $report = reportbuilder::create_embedded('perform_response_subject_instance', $config);
        [$sql, $sqlparams, ] = $report->build_query(false, false, false);
        $records = builder::get_db()->get_records_sql($sql, $sqlparams);

        $report_subject_instance_ids = [];
        $report_instance_numbers = [];
        foreach ($records as $record) {
            $report_subject_instance_ids[] = $record->id;
            $report_instance_numbers[] = $record->subject_instance_instance_number;
            self::assertEquals(3, $record->subject_instance_participant_count_performance_reporting);
        }
        self::assertEqualsCanonicalizing($subject_instances_user_1->pluck('id'), $report_subject_instance_ids);
        self::assertEqualsCanonicalizing([1, 2], $report_instance_numbers);
    }

    public function test_capabilities() {
        self::setAdminUser();

        $generator = \mod_perform\testing\generator::instance();
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_manager_for_each_subject_user()
            ->set_number_of_users_per_user_group_type(1);
        /** @var activity $activity */
        $generator->create_full_activities($config);

        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()->one();
        $subject_user_id = $subject_instance->subject_user_id;

        // Set up report.
        $config = new rb_config();
        $config->set_embeddata(['subject_user_id' => $subject_user_id]);
        $report = reportbuilder::create_embedded('perform_response_subject_instance', $config);
        $embedded_object = $report->embedobj;

        self::assertTrue($embedded_object->is_capable(get_admin()->id, $report));

        $manager = self::getDataGenerator()->create_user();
        $user = self::getDataGenerator()->create_user();

        self::assertFalse($embedded_object->is_capable($user->id, $report));

        $staffmanager_role = builder::get_db()->get_record('role', ['shortname' => 'staffmanager']);
        assign_capability(
            'mod/perform:report_on_subject_responses',
            CAP_ALLOW,
            $staffmanager_role->id,
            context_user::instance($user->id)->id,
            true
        );

        $manja = job_assignment::create_default($manager->id);
        job_assignment::create_default($user->id, ['managerjaid' => $manja->id]);

        self::assertTrue($embedded_object->is_capable($manager->id, $report));

        $user_role = builder::get_db()->get_record('role', ['shortname' => 'user']);
        assign_capability(
            'mod/perform:report_on_all_subjects_responses',
            CAP_ALLOW,
            $user_role->id,
            context_user::instance($user->id)->id,
            true
        );
        self::assertTrue($embedded_object->is_capable($user->id, $report));
    }

    public function test_tenant_domain_manager_viewing_performance_activity_response_data() {
        global $DB;
        set_config('tenantsenabled', 1);
        set_config('tenantsisolated', 0);
        $generator = $this->getDataGenerator();

        /** @var totara_tenant_generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant_user_manager_role = $DB->get_record('role', array('shortname' => 'tenantusermanager'), '*', MUST_EXIST);
        assign_capability(
            'mod/perform:report_on_all_subjects_responses',
            CAP_ALLOW,
            $tenant_user_manager_role->id,
            context_tenant::instance($tenant1->id)->id
        );

        // Tenant 1 users
        $tenant1_user = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_manager = $generator->create_user(['tenantid' => $tenant1->id]);
        $tenant1_admin = $generator->create_user(
            [
                'tenantid' => $tenant1->id,
                'tenantusermanager' => $tenant1->idnumber,
                'tenantdomainmanager' => $tenant1->idnumber,
            ]
        );

        // Create activity
        self::setUser($tenant1_admin);
        /** @var $container perform|\core_container\container*/
        $container = perform::create((object) [
            'container_name' =>  'tenant 1 Container',
            'category' => $tenant1->categoryid,
        ]);

        /** @var mod_perform_generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');
        $manager_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $activity = activity::create($container, 'tenant activity', activity_type::load_by_name('appraisal'));
        $track = track::create($activity)->add_assignment(
            track_assignment_type::ADMIN,
            grouping::by_type(grouping::COHORT, $tenant1->cohortid)
        );
        $section = section::create($activity)->update_relationships([
            [
                'core_relationship_id' => $manager_relationship->id,
                'can_view' => true,
                'can_answer' => true,
            ],
            [
                'core_relationship_id' => $subject_relationship->id,
                'can_view' => true,
                'can_answer' => true,
            ],
        ]);
        $element = element::create(
            context_tenant::instance($tenant1->id),
            'short_text',
            'Title',
            'A2 Element'
        );
        $section->section_element_manager->add_element_after($element);
        $activity->activate();
        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        // Set up report.
        $config = new rb_config();
        $config->set_embeddata(['subject_user_id' => $tenant1_user->id]);
        $report = reportbuilder::create_embedded('perform_response_subject_instance', $config);

        // Test records query
        [$sql, $sqlparams, ] = $report->build_query(false, false, false);
        $records = builder::get_db()->get_records_sql($sql, $sqlparams);
        $this->assertCount(1, $records);

        // test count query
        $this->assertEquals(1, $report->get_filtered_count());
    }
}
