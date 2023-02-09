<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\totara_notification\resolver;

use container_workspace\totara_notification\placeholder\enrolment as enrolment_placeholder;
use container_workspace\totara_notification\placeholder\workspace as workspace_placeholder;
use container_workspace\totara_notification\recipient\workspace_owner;
use context_course;
use core_user\totara_notification\placeholder\user as user_placeholder;
use lang_string;
use totara_core\extended_context;
use totara_notification\placeholder\placeholder_option;
use totara_notification\recipient\manager;
use totara_notification\recipient\subject;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * This notification covers users who were added to a workspace by someone else (such as an audience or by an owner)
 */
class user_added extends notifiable_event_resolver implements permission_resolver, audit_resolver {
    /**
     * @inheritDoc
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'container/workspace:administrate';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'container/workspace:administrate';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'recipient',
                user_placeholder::class,
                new lang_string('placeholder_group_recipient', 'totara_notification'),
                function (array $event_data, int $target_user_id): user_placeholder {
                    return user_placeholder::from_id($target_user_id);
                }
            ),
            placeholder_option::create(
                'subject',
                user_placeholder::class,
                new lang_string('placeholder_group_subject', 'totara_notification'),
                function (array $event_data): user_placeholder {
                    return user_placeholder::from_id($event_data['user_id']);
                }
            ),
            placeholder_option::create(
                'workspace',
                workspace_placeholder::class,
                new lang_string('notification_workspace_placeholder_group', 'container_workspace'),
                function (array $event_data): workspace_placeholder {
                    return workspace_placeholder::from_id($event_data['workspace_id']);
                }
            ),
            placeholder_option::create(
                'enrolment',
                enrolment_placeholder::class,
                new lang_string('notification_workspace_enrolment_placeholder_group', 'container_workspace'),
                function (array $event_data): enrolment_placeholder {
                    return enrolment_placeholder::from_workspace_id_and_user_id(
                        $event_data['workspace_id'],
                        $event_data['user_id']
                    );
                }
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_recipients(): array {
        return [
            subject::class,
            manager::class,
            workspace_owner::class,
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
    public static function get_notification_title(): string {
        return get_string('notification_user_added_resolver_title', 'container_workspace');
    }

    /**
     * @inheritDocs
     * @throws \coding_exception
     */
    public static function get_plugin_name(): ?string {
        return get_string('pluginname', 'container_workspace');
    }

    /**
     * @inheritDoc
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            return $context->contextlevel == CONTEXT_SYSTEM;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public static function uses_on_event_queue(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(
            context_course::instance($this->event_data['workspace_id']),
        );
    }

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $user = user_placeholder::from_id($this->get_event_data()['user_id']);
        $params['user'] = $user->do_get('full_name');

        $workspace = workspace_placeholder::from_id($this->get_event_data()['workspace_id']);
        $params['workspace'] = $workspace->do_get('full_name');

        return [
            'key' => 'notification_log_user_added',
            'component' => 'container_workspace',
            'params' => $params
        ];
    }

}
