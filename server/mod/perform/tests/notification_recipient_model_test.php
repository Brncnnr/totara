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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\notification_recipient;
use mod_perform\models\activity\notification_recipient as notification_recipient_model;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @covers \mod_perform\models\activity\notification_recipient
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_recipient_model_testcase extends mod_perform_notification_testcase {
    public function test_create_standard() {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $notification = notification::load_by_activity_and_class_key($activity, 'instance_created');
        $relationships = $this->create_section_relationships($section);
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_SUBJECT]->id)->active
        );
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_APPRAISER]->id)->active
        );
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_MANAGER]->id)->active
        );
    }

    public function test_create_manual() {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $notification = notification::load_by_activity_and_class_key($activity, 'participant_selection');
        $manuals = [constants::RELATIONSHIP_PEER, constants::RELATIONSHIP_MENTOR, constants::RELATIONSHIP_REVIEWER];
        $relationships = $this->create_section_relationships($section, array_merge($manuals, $this->get_default_relationships_for_testing()));

        $notification = notification::load_by_activity_and_class_key($activity, 'instance_created_reminder');
        $manuals = [constants::RELATIONSHIP_PEER, constants::RELATIONSHIP_MENTOR, constants::RELATIONSHIP_REVIEWER];
        $relationships = $this->create_section_relationships($section, array_merge($manuals, $this->get_default_relationships_for_testing()));
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_PEER]->id)->active
        );
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_MENTOR]->id)->active
        );
        $this->assertFalse(
            $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_REVIEWER]->id)->active
        );
    }

    public function test_load_by_notification_full() {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $notification = notification::load_by_activity_and_class_key($activity, 'due_date');
        $manuals = [constants::RELATIONSHIP_PEER, constants::RELATIONSHIP_MENTOR, constants::RELATIONSHIP_REVIEWER];
        $relationships = $this->create_section_relationships($section, array_merge($manuals, $this->get_default_relationships_for_testing()));

        $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_APPRAISER]->id)->activate();

        $this->assertCount(7, notification_recipient::load_by_notification($notification, false));
        $this->assertCount(1, notification_recipient::load_by_notification($notification, true));
    }

    public function test_load_by_notification_partial() {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $notification = notification::load_by_activity_and_class_key($activity, 'due_date');
        $relationships = $this->create_section_relationships(
            $section,
            [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_PEER]
        );
        $notification->recipients->find('core_relationship_id', $relationships[constants::RELATIONSHIP_MANAGER]->id)->activate();
        $this->assertCount(3, notification_recipient::load_by_notification($notification, false));
        $this->assertCount(1, notification_recipient::load_by_notification($notification, true));

        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $this->create_section_relationships(
            $section,
            [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_EXTERNAL]
        );
        $notification = notification::load_by_activity_and_class_key($activity, 'completion');
        $this->assertCount(1, notification_recipient::load_by_notification($notification, false));
        $this->assertCount(0, notification_recipient::load_by_notification($notification, true));
    }

    public function test_toggle(): void {
        $activity = $this->create_activity();
        $section = $this->create_section($activity);
        $this->create_section_relationships($section, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_APPRAISER]);

        $parent_notification = notification::load_by_activity_and_class_key($activity, 'due_date');
        /** @var notification_recipient_model $recipient */
        $recipient = $parent_notification->recipients->first();
        /** @var notification_recipient_model $other_recipient */
        $other_recipient = $parent_notification->recipients->last();

        $this->assertFalse($recipient->active);
        $this->assertFalse($other_recipient->active);
        $this->assertFalse($parent_notification->active);

        $recipient->toggle(true);
        $this->assertTrue($recipient->refresh()->active);
        $this->assertFalse($other_recipient->refresh()->active);
        $this->assertFalse($parent_notification->refresh()->active);

        $recipient->toggle(false);
        $this->assertFalse($recipient->refresh()->active);
        $this->assertFalse($other_recipient->refresh()->active);
        $this->assertFalse($parent_notification->refresh()->active);

        $recipient->activate();
        $this->assertTrue($recipient->refresh()->active);
        $this->assertFalse($other_recipient->refresh()->active);
        $this->assertFalse($parent_notification->refresh()->active);

        $recipient->deactivate();
        $this->assertFalse($recipient->refresh()->active);
        $this->assertFalse($other_recipient->refresh()->active);
        $this->assertFalse($parent_notification->refresh()->active);
    }

}
