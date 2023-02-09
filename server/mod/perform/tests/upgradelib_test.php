<?php
/*
 * This file is part of Totara Learn
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/perform/db/upgradelib.php');

use core\json_editor\helper\document_helper;
use mod_perform\constants;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\notification\factory;
use mod_perform\state\activity\draft;
use mod_perform\totara_notification\recipient\perform_mentor as recipient_mentor;
use totara_job\job_assignment;

/**
 * @group perform
 */
class mod_perform_upgradelib_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;

    private $perfgen = null;

    public function setUp(): void {
        parent::setUp();

        $this->perfgen = \mod_perform\testing\generator::instance();
        $this->setAdminUser();
    }


    public function tearDown(): void {
        $this->perfgen = null;

        parent::tearDown();
    }

    /**
     * Set up an appraisal with some old notifications to run the upgrade on.
     *
     * @param int $cohort_id
     * @param array $data
     * @return activity_model
     */
    private function create(int $cohort_id = null, array $data = []): activity_model {

        if (!isset($data['activity_status'])) {
            $data['activity_status'] = draft::get_code();
        }

        if (!isset($data['create_section'])) {
            $data['create_section'] = false;
        }

        $activity = $this->perfgen->create_activity_in_container($data);
        $section = $this->perfgen->create_section($activity, ['title' => $data['activity_name'] . "'s section"]);
        $this->perfgen->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_SUBJECT]);
        $this->perfgen->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_MANAGER]);
        $this->perfgen->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_MANAGERS_MANAGER]);

        $element = $this->perfgen->create_element(['title' => $data['activity_name'] . "'s element"]);
        section_element_model::create($section, $element, 1);
        $track = $this->perfgen->create_activity_tracks($activity, 1)->first(true);

        if (!empty($cohort_id)) {
            $this->perfgen->create_track_assignments_with_existing_groups($track, [$cohort_id]);
        }

        foreach (factory::create_loader()->get_class_keys() as $class_key) {
            $notification = notification::load_by_activity_and_class_key($activity, $class_key)->activate();
            $this->perfgen->create_notification_recipient($notification, ['idnumber' => constants::RELATIONSHIP_SUBJECT], true);
            $this->perfgen->create_notification_recipient($notification, ['idnumber' => constants::RELATIONSHIP_MANAGER], true);
            $this->perfgen->create_notification_recipient($notification, ['idnumber' => constants::RELATIONSHIP_MANAGERS_MANAGER], true);
        }
        return $activity;
    }

    /**
     * @param activity_model $activity - an array of activity models
     * @param array $users - the array of users
     * @return void
     */
    private function activate(activity_model $activity, array $users): void {
        $activity->activate();
        $subject_instance = $this->perfgen->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $users['subject']->id,
            'include_questions' => false,
        ]);
        $this->assertNotNull($subject_instance, $activity->name);
        $section = $activity->get_sections()->first();

        $manager_section_relationship = $this->perfgen->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );
        $managers_manager_section_relationship = $this->perfgen->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGERS_MANAGER]
        );
        $subject_section_relationship = $this->perfgen->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );

        $this->perfgen->create_participant_instance_and_section(
            $activity,
            $users['subject'],
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );
        $this->perfgen->create_participant_instance_and_section(
            $activity,
            $users['manager'],
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );
        $this->perfgen->create_participant_instance_and_section(
            $activity,
            $users['supervisor'],
            $subject_instance->id,
            $section,
            $managers_manager_section_relationship->core_relationship_id
        );
    }

    /**
     * Fetch the notification preference records that should be enabled by default for perform activities.
     * @return array
     */
    private static function fetch_default_notif_prefs(): array {
        global $DB;

        // Annoyingly since they inherit values from files, this is the only way to identify the enabled defaults.
        list($in_sql, $params) = $DB->get_in_or_equal(
            [
                'mod_perform\totara_notification\notification\participant_selection',
                'mod_perform\totara_notification\notification\participant_selection_for_subject',
                'mod_perform\totara_notification\notification\participant_instance_created',
                'mod_perform\totara_notification\notification\participant_instance_created_for_subject'
            ],
            SQL_PARAMS_NAMED
        );

        // Fetch the system context.
        $params['cid'] = \context_system::instance()->id;

        $sql = 'SELECT *
                  FROM {notification_preference}
                 WHERE context_id = :cid
                   AND notification_class_name ' . $in_sql;

        // Fetch the enabled default notifications.
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * @group perform
     */
    public function test_mod_perform_upgradelib_migrate_notifications() {
        global $DB;

        $timestamp = time() + DAYSECS; // So we can identify the records.

        $user = $this->getDataGenerator()->create_user(['username' => 'subject']);
        $manager = $this->getDataGenerator()->create_user(['username' => 'manager']);
        $supervisor = $this->getDataGenerator()->create_user(['username' => 'supervisor']);

        $superja = job_assignment::create_default($supervisor->id);
        $manja = job_assignment::create_default($manager->id, ['managerjaid' => $superja->id]);
        $userja = job_assignment::create_default($user->id, ['managerjaid' => $manja->id]);

        $users = [
            'subject' => $user,
            'manager' => $manager,
            'supervisor' => $supervisor
        ];

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user->id);

        // Create some activities in several states.
        $activity_draft = $this->create($cohort->id, ['activity_name' => 'draft']);
        $activity_open = $this->create($cohort->id, ['activity_name' => 'open']);

        // Get them into the stated states :)
        $this->activate($activity_open, $users);

        // Lets quickly make a multi-schedule notification for activity_open.
        $triggers = json_encode([DAYSECS, DAYSECS * 5]);
        $DB->execute("
            UPDATE {perform_notification}
               SET triggers = '{$triggers}'
             WHERE activity_id = :act
               AND class_key = :key",
            ['act' => $activity_open->id, 'key' => 'due_date_reminder']
        );

        // We need to pass these through to prevent multiple fetches.
        $relations = $DB->get_records('totara_core_relationship');

        // Now lets get the existing notifications.
        $legacies = $DB->get_records('perform_notification', ['activity_id' => $activity_open->id]);

        // And figure out how many new notifications we are expecting.
        $expected = 0;
        $multiples = 0;
        foreach ($legacies as $legacy) {
            $legacy->recipients = mod_perform_get_recipients($legacy, $relations);

            $triggers = json_decode($legacy->triggers);
            $trigger_count = empty($triggers) ? 1 : count($triggers);
            $expected += $trigger_count;
        }

        // And onto the big one, lets make sure the open activity does get migrated.
        mod_perform_migrate_notifications($activity_open, $relations, [], $timestamp);
        $notifications = $DB->get_records('notification_preference', ['time_created' => $timestamp]);
        $this->assertCount($expected * 2, $notifications); // Including recipients of the multi-schedule one created.

        // We have the expected amount of records, lets check the contents a little.
        $context = $activity_open->get_context();
        foreach ($notifications as $notification) {
            self::assertEquals($context->id, $notification->context_id);

            self::assertTrue(document_helper::is_valid_json_document($notification->body));
            self::assertTrue(document_helper::is_valid_json_document($notification->subject));

            // Check it is enabled and filled with valid classes.
            self::assertTrue(class_exists($notification->resolver_class_name), $notification->resolver_class_name);
            self::assertTrue(class_exists($notification->recipient), $notification->recipient);

            $recipients = json_decode($notification->recipients);
            self::assertTrue(is_array($recipients));
            foreach ($recipients as $recipient) {
                self::assertTrue(class_exists($recipient), $recipient);
            }
        }

        // Enable multilang filter.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', 1);
        $filtermanager = filter_manager::instance();
        $filtermanager->reset_caches();

        // Create a fake language pack.
        $this->add_fake_language_pack('fake', [
            'mod_perform' => [
                'template_completion_perform_mentor_subject' => 'fake string',
                'template_due_date_perform_mentor_subject' => 'fake string',
                'template_due_date_reminder_perform_mentor_body' => 'fake string',
                'template_instance_created_perform_mentor_body' => 'fake string',
                'template_instance_created_reminder_perform_mentor_body' => 'fake string',
                'template_overdue_reminder_perform_mentor_body' => 'fake string',
                'template_reopened_perform_mentor_subject' => 'fake string',
                'template_reopened_perform_mentor_body' => 'fake string',
            ]
        ]);

        // And migrate the draft performance activity.
        mod_perform_migrate_notifications($activity_draft, $relations, [], $timestamp);

        $expected--; // This one doesn't have the multi-trigger notification so there's one less.
        $context = $activity_draft->get_context();
        $notifications = $DB->get_records('notification_preference', ['context_id' => $context->id]);
        $this->assertCount(($expected * 2) + 7, $notifications); // We've overidden 7 notification strings.

        // To check the multi-lang upgrade of perform notifications.
        foreach ($notifications as $notification) {
            self::assertEquals($context->id, $notification->context_id);

            self::assertStringContainsString('weka_simple_multi_lang_lang_blocks', $notification->body);
            self::assertTrue(document_helper::is_valid_json_document($notification->body));

            $criteria = json_decode($notification->additional_criteria ?? '');
            $recipients = $criteria->recipients ?? [];
            $contains = str_contains($notification->subject, 'fake string') || str_contains($notification->body, 'fake string');
            if ($notification->recipient == recipient_mentor::class || in_array('perform_mentor', $recipients)) {
                self::assertTrue($contains);
            } else {
                self::assertFalse($contains);
            }

            $this->assertStringContainsString('weka_simple_multi_lang_lang_blocks', $notification->subject);
            self::assertTrue(document_helper::is_valid_json_document($notification->subject));
        }
    }

    /**
     * @group perform
     */
    public function test_mod_perform_upgradelib_migrate_disables_defaults() {
        global $DB;

        $timestamp = time() + DAYSECS; // So we can identify the records.

        // Create some activities in several states.
        $activity1 = $this->create(null, ['activity_name' => 'control']);
        $activity2 = $this->create(null, ['activity_name' => 'testing']);

        // We need to pass these through to prevent multiple fetches.
        $relations = $DB->get_records('totara_core_relationship');
        $defaults = self::fetch_default_notif_prefs();

        // Now lets get the existing notifications.
        $legacies = $DB->get_records('perform_notification', ['activity_id' => $activity2->id]);

        // Figure out how many new notifications we are expecting.
        $expected = 0;
        foreach ($legacies as $legacy) {
            $legacy->recipients = mod_perform_get_recipients($legacy, $relations);
            $expected++;
        }

        // And onto the big one, lets make sure the existing activity has defaults disabled.
        mod_perform_migrate_notifications($activity2, $relations, $defaults, $timestamp);

        // Quick check on the amount of migrated preferences.
        $context = $activity2->get_context();
        $notifications = $DB->get_records('notification_preference', ['context_id' => $context->id]);
        $this->assertCount(($expected * 2) + count($defaults), $notifications); // Including overrides for defaults.

        // And the main thing here, lets check the contents of the defaults a little.
        foreach ($defaults as $default) {
            // Get the activity version of the default.
            $override = $DB->get_record(
                'notification_preference',
                [
                    'context_id' => $context->id,
                    'ancestor_id' => $default->id
                ]
            );

            // Check we overrode what we needed to.
            self::assertEquals(0, $override->enabled);

            // And that everything else should inherit correctly.
            self::assertNull($override->body);
            self::assertNull($override->title);
            self::assertNull($override->subject);
        }
    }

    /**
     * @group perform
     */
    public function test_mod_perform_upgradelib_migrate_multiple_runs() {
        global $DB;

        $timestamp = time() + DAYSECS; // So we can identify the records.

        // Create some activities in several states.
        $activity1 = $this->create(null, ['activity_name' => 'control']);
        $activity2 = $this->create(null, ['activity_name' => 'testing']);

        // We need to pass these through to prevent multiple fetches.
        $relations = $DB->get_records('totara_core_relationship');
        $defaults = self::fetch_default_notif_prefs();

        // Now lets get the existing notifications.
        $legacies = $DB->get_records('perform_notification', ['activity_id' => $activity2->id]);

        // Figure out how many new notifications we are expecting.
        $expected = 0;
        foreach ($legacies as $legacy) {
            $legacy->recipients = mod_perform_get_recipients($legacy, $relations);
            $expected++;
        }
        $expected = ($expected * 2) + count($defaults); // 2 CN per legacy + override defaults.

        // Run The first activity.
        $result = mod_perform_migrate_notifications($activity1, $relations, $defaults, $timestamp);
        $this->assertTrue($result);

        // Quick check on the amount of migrated preferences.
        $ctx1 = $activity1->get_context();
        $notifications = $DB->get_records('notification_preference', ['context_id' => $ctx1->id]);
        $this->assertCount($expected, $notifications);

        // Check that hasn't done anything to activity 2.
        $ctx2 = $activity2->get_context();
        $notifications = $DB->get_records('notification_preference', ['context_id' => $ctx2->id]);
        $this->assertCount(0, $notifications);

        // Run the second activity, and make sure it hasn't been affected by the first one running.
        $result = mod_perform_migrate_notifications($activity2, $relations, $defaults, $timestamp);
        $this->assertTrue($result);

        // Quick check on the amount of migrated preferences.
        $notifications = $DB->get_records('notification_preference', ['context_id' => $ctx2->id]);
        $this->assertCount($expected, $notifications);

        // Run it again and make sure no new notifications are created.
        $result = mod_perform_migrate_notifications($activity2, $relations, $defaults, $timestamp);
        $this->assertFalse($result);

        // Quick check that we haven't made any duplicate preferences.
        $notifications = $DB->get_records('notification_preference', ['context_id' => $ctx2->id]);
        $this->assertCount($expected, $notifications); // Including overrides for defaults.

    }
}
