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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\interactor;

use context_user;
use totara_core\extended_context;
use totara_notification\factory\capability_factory;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\resolver_helper;

class notification_audit_interactor extends notification_interactor {

    /**
     * @var ?int
     */
    protected ?int $target_user_id;

    public function __construct(extended_context $extended_context, int $interactor_user_id, ?int $target_user_id = null) {
        parent::__construct($extended_context, $interactor_user_id);
        $this->target_user_id = $target_user_id;
    }

    /**
     * @param string[] $extra_capabilities
     * @return bool
     */
    public function has_any_capability_for_context(array $extra_capabilities = []): bool {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled)) {
            return false;
        }

        if ($this->target_user_id) {
            $user_context = context_user::instance($this->target_user_id);

            // When the target user is the same as the log-in user, then log-in user should have either
            // the 'auditownnotifications' or 'auditnotifications' capability see the logs.
            if ($this->target_user_id == $this->user_id) {
                $audit_own_notification = has_capability('totara/notification:auditownnotifications', $user_context, $this->user_id);
                $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context, $this->user_id);

                return $audit_own_notification || $audit_all_notification;
            } else {
                // When the target user is not the same as the log-in user, then log-in user should have 'auditnotifications'
                // capability to see the notification log.
                $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context, $this->user_id);

                return $audit_all_notification;
            }
        }

        $context = $this->extended_context->get_context();
        $context_level = $this->extended_context->get_context_level();
        $notification_capabilities = capability_factory::get_audit_capabilities($context_level);

        $notification_capabilities = array_merge($extra_capabilities, $notification_capabilities);

        return has_any_capability($notification_capabilities, $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_audit_notifications(): bool {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled)) {
            return false;
        }

        if ($this->target_user_id) {
            $user_context = context_user::instance($this->target_user_id);

            // When the target user is the same as the log-in user, then log-in user should have either
            // the 'auditownnotifications' or 'auditnotifications' capability see the logs.
            if ($this->target_user_id == $this->user_id) {
                $audit_own_notification = has_capability('totara/notification:auditownnotifications', $user_context, $this->user_id);
                $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context, $this->user_id);

                return $audit_own_notification || $audit_all_notification;
            } else {
                // When the target user is not the same as the log-in user, then log-in user should have 'auditnotifications'
                // capability to see the notification log.
                $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context, $this->user_id);

                return $audit_all_notification;
            }
        } else {
            $context = $this->extended_context->get_context();
            $audit_all_notification = has_capability('totara/notification:auditnotifications', $context, $this->user_id);

            return $audit_all_notification;
        }
    }

    /**
     * Checks the user's permission to audit notifications at the extended context level.
     *
     * @param string $resolver_class_name
     * @return bool
     */
    public function can_audit_notifications_of_resolver(string $resolver_class_name): bool {
        $logs_enabled = get_config('core','notificationlogs');
        if (empty($logs_enabled)) {
            return false;
        }

        resolver_helper::validate_event_resolver($resolver_class_name);

        if ($this->can_audit_notifications()) {
            // If user has a general auditing preferences permission, then we
            // dont have to perform the further check.
            return true;
        }

        if (!resolver_helper::is_valid_audit_resolver($resolver_class_name)) {
            // The resolver class name does not implement the interface audit_resolver.
            // Hence it should be FALSE for this case, because if user has a permission, then this
            // check will never be executed.
            return false;
        }

        /** @see audit_resolver::can_user_audit_notifications() */
        return call_user_func_array(
            [$resolver_class_name, 'can_user_audit_notifications'],
            [$this->extended_context, $this->user_id]
        );
    }
}