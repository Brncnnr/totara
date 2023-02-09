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
 */

defined('MOODLE_INTERNAL') || die();

// Please do not include this file and parse the contents!
// Instead, use the loader instance returned by mod_perform\notification\factory::create_loader().

$notifications = [
    'participant_selection' => [ // 23
        'name' => ['notification_broker_participant_selection', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\participant_selection::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_ONCE,
        'recipients' => \mod_perform\notification\recipient::STANDARD,
        'all_possible_recipients' => true,
        'active_by_default' => true,
        'active_for_recipients' => \mod_perform\notification\recipient::STANDARD,
    ],
    'instance_created' => [ // 18
        'name' => ['notification_broker_instance_created', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\instance_created::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_ONCE,
        'recipients' => \mod_perform\notification\recipient::ALL,
        'active_by_default' => true,
        'active_for_recipients' => \mod_perform\notification\recipient::EXTERNAL,
    ],
    'instance_created_reminder' => [ // 19
        'name' => ['notification_broker_instance_created_reminder', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\instance_created_reminder::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_AFTER,
        'trigger_label' => ['notification_trigger_instance_creation', 'mod_perform'],
        'condition' => \mod_perform\notification\conditions\days_after::class,
        'recipients' => \mod_perform\notification\recipient::ALL,
        'is_reminder' => true,
    ],
    'due_date_reminder' => [ // 20
        'name' => ['notification_broker_due_date_reminder', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\due_date_reminder::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_BEFORE,
        'trigger_label' => ['notification_trigger_duedate', 'mod_perform'],
        'condition' => \mod_perform\notification\conditions\days_before::class,
        'recipients' => \mod_perform\notification\recipient::ALL,
        'is_reminder' => true,
    ],
    'due_date' => [ // 21
        'name' => ['notification_broker_due_date', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\due_date::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_ONCE,
        'condition' => \mod_perform\notification\conditions\after_midnight::class,
        'recipients' => \mod_perform\notification\recipient::ALL,
        'is_reminder' => true,
    ],
    'overdue_reminder' => [ // 22
        'name' => ['notification_broker_overdue_reminder', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\overdue_reminder::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_AFTER,
        'trigger_label' => ['notification_trigger_duedate', 'mod_perform'],
        'condition' => \mod_perform\notification\conditions\days_after::class,
        'recipients' => \mod_perform\notification\recipient::ALL,
        'is_reminder' => true,
    ],
    'completion' => [ // 25
        'name' => ['notification_broker_completion', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\completion::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_ONCE,
        'recipients' => \mod_perform\notification\recipient::STANDARD | \mod_perform\notification\recipient::MANUAL,
    ],
    'reopened' => [ // 26
        'name' => ['notification_broker_reopened', 'mod_perform'],
        'class' => \mod_perform\notification\brokers\reopened::class,
        'trigger_type' => \mod_perform\notification\trigger::TYPE_ONCE,
        'recipients' => \mod_perform\notification\recipient::ALL,
    ],
];
