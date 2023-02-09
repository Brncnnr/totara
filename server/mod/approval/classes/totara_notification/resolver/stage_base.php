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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */
namespace mod_approval\totara_notification\resolver;

use container_approval\approval as approval_container;
use core_container\factory as container_factory;
use core_user\totara_notification\placeholder\user as user_placeholder_group;
use coursecat;
use lang_string;
use mod_approval\model\application\activity\notification_sent as notification_sent_activity;
use mod_approval\model\application\application as application_model;
use mod_approval\model\application\application_activity;
use mod_approval\totara_notification\placeholder\application as application_placeholder_group;
use mod_approval\totara_notification\placeholder\recipient as recipient_placeholder_group;
use mod_approval\totara_notification\recipient\applicant;
use mod_approval\totara_notification\recipient\applicant_manager;
use totara_core\extended_context;
use totara_hierarchy\totara_notification\placeholder\organisation as organisation_placeholder_group;
use totara_notification\model\notification_preference;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_on_event;

abstract class stage_base extends notifiable_event_resolver implements permission_resolver {

    /**
     * @inheritDoc
     */
    public static function get_notification_available_recipients(): array {
        return [
            applicant::class,
            applicant_manager::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
            schedule_after_event::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'applicant',
                user_placeholder_group::class,
                new lang_string('notification:placeholder_group_applicant', 'mod_approval'),
                function (array $event_data): user_placeholder_group {
                    $application = application_model::load_by_id($event_data['application_id']);
                    return new user_placeholder_group($application->get_user());
                }
            ),
            placeholder_option::create(
                'application',
                application_placeholder_group::class,
                new lang_string('notification:placeholder_group_application', 'mod_approval'),
                function (array $event_data): application_placeholder_group {
                    return application_placeholder_group::from_id($event_data['application_id']);
                }
            ),
            placeholder_option::create(
                'applicant_job_assignment_organisation',
                organisation_placeholder_group::class,
                new lang_string('notification:placeholder_group_applicant_organisation', 'mod_approval'),
                function (array $event_data): organisation_placeholder_group {
                    $application = application_model::load_by_id($event_data['application_id']);
                    $job_assignment = $application->get_job_assignment();
                    if ($job_assignment && $job_assignment->organisationid) {
                        return organisation_placeholder_group::from_id($job_assignment->organisationid);
                    } else {
                        return new organisation_placeholder_group(null);
                    }
                }
            ),
            placeholder_option::create(
                'recipient',
                recipient_placeholder_group::class,
                new lang_string('notification:placeholder_group_recipient', 'mod_approval'),
                function (array $unused_event_data, int $target_user_id): recipient_placeholder_group {
                    return recipient_placeholder_group::from_id($target_user_id);
                }
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('mod/approval:manage_workflow_notifications', $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public function get_extended_context(): extended_context {
        $application = application_model::load_by_id($this->event_data['application_id']);
        return extended_context::make_with_context(
            $application->get_workflow_version()->get_workflow()->get_context(),
            'mod_approval',
            'workflow_stage',
            $this->event_data['workflow_stage_id']
        );
    }

    /**
     * @inheritDoc
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            switch ($context->contextlevel) {
                case CONTEXT_SYSTEM:
                    return true;
                case CONTEXT_COURSECAT:
                    $category = coursecat::get($context->instanceid);
                    return substr($category->idnumber, 0, strlen('container_approval-')) == 'container_approval-';
                case CONTEXT_COURSE:
                    $container = container_factory::from_id($context->instanceid);
                    return $container->is_typeof(approval_container::get_type());
                default:
                    return false;
            }
        }

        // We are only interested in extended contexts which are containers...
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return false;
        }

        // ... which are approval containers ...
        $container = container_factory::from_id($context->instanceid);
        if (!$container->is_typeof(approval_container::get_type())) {
            return false;
        }

        // ... and which are approval stages.
        return $context->contextlevel === CONTEXT_COURSE
            && $extended_context->get_component() === 'mod_approval'
            && $extended_context->get_area() === 'workflow_stage';
    }

    /**
     * When a notification is sent, we record it as an activity.
     *
     * @param notification_preference $preference
     */
    public function notification_sent(notification_preference $preference): void {
        $application = application_model::load_by_id($this->event_data['application_id']);

        application_activity::create(
            $application,
            null,
            notification_sent_activity::class,
            [
                'resolver_class_name' => static::class,
                'recipient_class_names' => $preference->get_recipients(),
            ]
        );
    }
}