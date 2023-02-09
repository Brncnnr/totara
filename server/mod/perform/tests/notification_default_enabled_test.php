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
 * @package mod_perform
 */

use container_perform\perform;
use core\collection;
use mod_perform\constants;
use mod_perform\entity\activity\notification as notification_entity;
use mod_perform\entity\activity\notification_recipient as notification_recipient_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\helpers\relationship_helper;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\notification_recipient;
use mod_perform\notification\broker;
use mod_perform\notification\factory;
use mod_perform\notification\loader;
use mod_perform\util;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @covers \mod_perform\observers\notification::create_notifications
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_default_enabled_testcase extends mod_perform_notification_testcase {

    /**
     * Make sure the specific notification has the correct state upon the creation of an activity.
     */
    public function test_default_notifications_exist(): void {
        $perform_relationships = relationship_helper::get_supported_perform_relationships();

        $this->assertEquals(0, notification_entity::repository()->count());
        $this->assertEquals(0, notification_recipient_entity::repository()->count());

        $activity = $this->create_activity();

        // Test the notifications that aren't active by default.
        foreach (loader::create()->get_class_keys() as $class_key) {
            if (in_array($class_key, [
                'participant_selection',
                'instance_created',
            ])) {
                // We test the active notifications later on.
                continue;
            }

            $notification = notification::load_by_activity_and_class_key($activity, $class_key);
            $this->assertFalse($notification->active);
            $all_recipient_relationships = relationship_entity::repository()
                ->join([notification_recipient_entity::TABLE, 'nr'], 'id', 'core_relationship_id')
                ->join([notification_entity::TABLE, 'n'], 'nr.notification_id', 'id')
                ->where('n.id', $notification->id)
                ->get()
                ->map_to(relationship::class);
            $this->assertEqualsCanonicalizing($perform_relationships->pluck('id'), $all_recipient_relationships->pluck('id'));
            $this->assertEquals(0, notification_recipient_entity::repository()
                ->where('notification_id', $notification->id)
                ->where('active', 1)
                ->count()
            );
        }

        // participant_selection is active by default
        $participant_selection_notification = notification::load_by_activity_and_class_key($activity, 'participant_selection');
        $this->assertTrue($participant_selection_notification->active);
        $this->assertEquals($perform_relationships->count(),
            notification_recipient_entity::repository()->where('notification_id', $participant_selection_notification->id)->count()
        );
        $this->assertEqualsCanonicalizing(
            $perform_relationships->filter('type', relationship_entity::TYPE_STANDARD)->pluck('id'),
            relationship_entity::repository()
                ->join([notification_recipient_entity::TABLE, 'nr'], 'id', 'core_relationship_id')
                ->join([notification_entity::TABLE, 'n'], 'nr.notification_id', 'id')
                ->where('n.id', $participant_selection_notification->id)
                ->where('nr.active', 1)
                ->get()
                ->pluck('id')
        );

        // instance_created is active by default
        $instance_created_notification = notification::load_by_activity_and_class_key($activity, 'instance_created');
        $this->assertTrue($instance_created_notification->active);
        $this->assertEquals($perform_relationships->count(),
            notification_recipient_entity::repository()->where('notification_id', $instance_created_notification->id)->count()
        );
        $this->assertEqualsCanonicalizing(
            $perform_relationships->filter('idnumber', constants::RELATIONSHIP_EXTERNAL)->pluck('id'),
            relationship_entity::repository()
                ->join([notification_recipient_entity::TABLE, 'nr'], 'id', 'core_relationship_id')
                ->join([notification_entity::TABLE, 'n'], 'nr.notification_id', 'id')
                ->where('n.id', $instance_created_notification->id)
                ->where('nr.active', 1)
                ->get()
                ->pluck('id')
        );
    }

    /**
     * @covers ::mod_perform_upgrade_create_missing_notification_records
     */
    public function test_upgraded_activities_have_notification_records(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');

        // This should always be the same as the notifications defined in mod_perform_create_missing_notification_records()
        $all_class_keys = [
            'completion',
            'due_date',
            'due_date_reminder',
            'instance_created',
            'instance_created_reminder',
            'overdue_reminder',
            'participant_selection',
            'reopened',
        ];

        $relationships = relationship_helper::get_supported_perform_relationships();


        $activity1 = $this->create_activity(['name' => 'Activity with no notifications']);
        $activity2 = $this->create_activity(['name' => 'Activity with one notification and no recipients']);
        $activity3 = $this->create_activity(['name' => 'Activity with 2 notifications and some recipients']);
        $activity4 = $this->create_activity(['name' => 'Activity with all notifications and all recipients']);

        // As notifications are now created upon creation of the activity,
        // we must simulate how notifications used to work, where actual records weren't created until later on.
        // We truncate the notification tables to simulate this.
        notification_entity::repository()->delete();
        notification_recipient_entity::repository()->delete();


        // Activity2 has one notification
        notification::create($activity2, 'due_date');
        // Activity3 has 2 notifications and 2 recipients
        $act3_not1 = notification::create($activity3, 'due_date', true);
        $act3_not1_rec1 = notification_recipient::create($act3_not1, $relationships->first(), true);
        $act3_not2 = notification::create($activity3, 'overdue_reminder', false);
        notification_recipient::create($act3_not2, $relationships->last(), false);
        // Activity4 has all notifications and recipients
        notification::create_all_for_activity($activity4);
        $act4_not1 = notification::load_by_activity_and_class_key($activity4, 'due_date_reminder');
        $act4_not1_trigger = [10]; // 10 days
        $act4_not1->set_triggers($act4_not1_trigger);
        $act4_not2_trigger = notification::load_by_activity_and_class_key($activity4, 'overdue_reminder')
            ->get_triggers(); // Should be just one day

        // Run the upgrade step.
        mod_perform_upgrade_create_missing_notification_records([
            'completion' => [],
            'due_date' => [],
            'due_date_reminder' => [86400], // Trigger: 1 day (in seconds)
            'instance_created' => [],
            'instance_created_reminder' => [86400], // Trigger: 1 day (in seconds)
            'overdue_reminder' => [86400], // Trigger: 1 day (in seconds)
            'participant_selection' => [],
            'reopened' => [],
        ]);

        // Make sure all notification and recipient records exist.
        foreach ([$activity1, $activity2, $activity3, $activity4] as $activity) {
            $this->assertEqualsCanonicalizing(
                $all_class_keys,
                notification_entity::repository()
                    ->select('class_key')
                    ->where('activity_id', $activity->id)
                    ->get()
                    ->pluck('class_key')
            );

            foreach ($all_class_keys as $class_key) {
                // Make sure all the relationships now exist.
                $this->assertEqualsCanonicalizing(
                    $relationships->pluck('id'),
                    notification_recipient_entity::repository()
                        ->select('core_relationship_id')
                        ->join([notification_entity::TABLE, 'n'], 'notification_id', 'id')
                        ->where('n.activity_id', $activity->id)
                        ->where('n.class_key', $class_key)
                        ->get()
                        ->pluck('core_relationship_id')
                );

                // Make sure the triggers created are the same as what is defined in their broker class.
                $expected_trigger = json_encode(factory::create_broker($class_key)->get_default_triggers(), JSON_UNESCAPED_SLASHES);
                $notification = notification_entity::repository()
                    ->where('activity_id', $activity->id)
                    ->where('class_key', $class_key)
                    ->one(true);

                if ($notification->id === $act4_not1->id) {
                    // We changed the trigger to be 10 days instead of the default, so we don't assert it.
                    continue;
                }
                $this->assertSame($expected_trigger, $notification->triggers);
            }
        }

        // Make sure Activity3's notifications are still active
        $act3_not1_updated = new notification_entity($act3_not1->id);
        $this->assertEquals($activity3->id, $act3_not1_updated->activity_id);
        $this->assertTrue($act3_not1_updated->active);
        $act3_not1_rec1_updated = new notification_recipient_entity($act3_not1_rec1->id);
        $this->assertEquals($act3_not1->id, $act3_not1_rec1_updated->notification_id);
        $this->assertTrue($act3_not1_rec1_updated->active);

        // Activity4's triggers shouldn't have changed
        $this->assertEquals(
            $act4_not1_trigger,
            notification::load_by_activity_and_class_key($activity4, 'due_date_reminder')->get_triggers()
        );
        $this->assertEquals(
            $act4_not2_trigger,
            notification::load_by_activity_and_class_key($activity4, 'overdue_reminder')->get_triggers()
        );

        // When fetching the notifications, they should be sorted by their class_key position in notifications.php, and not by ID.
        // (See server/mod/perform/db/notifications.php)
        $activity3_all_notifications = notification::load_all_by_activity($activity3);
        $this->assertEquals(
            factory::create_loader()->get_class_keys(),
            $activity3_all_notifications->pluck('class_key'),
            'notification::load_all_by_activity() must sort by the order of where the class_key is in notifications.php'
        );
    }

    /**
     * Check that the notifications array defined in /mod/perform/db/notifications.php is always in a valid state.
     */
    public function test_notifications_db_definition_file_is_valid(): void {
        global $CFG;
        require($CFG->dirroot . '/mod/perform/db/notifications.php');

        if (!isset($notifications)) {
            $this->fail('/mod/perform/db/notifications.php must contain a $notifications variable.');
        }

        if (!is_array($notifications)) {
            $this->fail('The $notifications variable in /mod/perform/db/notifications.php must be an array.');
        }

        $brokers = collection::new(
            core_component::get_namespace_classes('notification\brokers', broker::class, 'mod_perform')
        )->map(static function (string $broker_class) {
            return (new ReflectionClass($broker_class))->getShortName();
        });

        foreach ($brokers as $broker) {
            if (!isset($notifications[$broker])) {
                $this->fail(
                    "'$broker' has a broker class, " .
                    "but it is not defined as a notification in /mod/perform/db/notifications.php"
                );
            }
            if (!is_array($notifications[$broker])) {
                $this->fail("The entry for '$broker' in /mod/perform/db/notifications.php must be an array.");
            }
        }

        $this->assertEqualsCanonicalizing(
            $brokers->all(),
            array_keys($notifications),
            "The notification keys defined in /mod/perform/db/notifications.php " .
            "must match the broker classes defined in \\notification\brokers"
        );
    }

    /**
     * Create an activity for testing.
     *
     * @param array $data
     * @return activity
     */
    protected function create_activity(array $data = []): activity {
        self::setAdminUser();
        $name = $data['name'] ?? 'Activity';
        $container = perform::create((object) [
            'container_name' => $name . ' Container',
            'category' => util::get_default_category_id(),
        ]);
        return activity::create(
            $container, $name, activity_type::load_by_name('appraisal')
        );
    }

}
