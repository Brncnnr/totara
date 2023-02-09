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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\interactor;

use totara_notification\factory\capability_factory;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\resolver_helper;

class notification_preference_interactor extends notification_interactor {
    /**
     * @param string[] $extra_capabilities
     * @return bool
     */
    public function has_any_capability_for_context(array $extra_capabilities = []): bool {
        $context_level = $this->extended_context->get_context_level();
        $notification_capabilities = capability_factory::get_manage_capabilities($context_level);

        $notification_capabilities = array_merge($extra_capabilities, $notification_capabilities);
        $context = $this->extended_context->get_context();

        return has_any_capability($notification_capabilities, $context, $this->user_id);
    }

    /**
     * @return bool
     */
    public function can_manage_notification_preferences(): bool {
        $context = $this->extended_context->get_context();

        return has_capability(
            'totara/notification:managenotifications',
            $context,
            $this->user_id
        );
    }

    /**
     * Checks the user's permission whether user able to create a new notification preference for the
     * resolver at different context.
     *
     * @param string $resolver_class_name
     * @return bool
     */
    public function can_manage_notification_preferences_of_resolver(string $resolver_class_name): bool {
        resolver_helper::validate_event_resolver($resolver_class_name);

        if ($this->can_manage_notification_preferences()) {
            // If user has a general managing notification preferences permission, then we
            // dont have to perform the further check.
            return true;
        }

        if (!resolver_helper::is_valid_permission_resolver($resolver_class_name)) {
            // The resolver class name does not implement the interface permission_resolver.
            // Hence it should be FALSE for this case, because if user has a permission, then this
            // check will never be executed.
            return false;
        }

        /** @see permission_resolver::can_user_manage_notification_preferences() */
        return call_user_func_array(
            [$resolver_class_name, 'can_user_manage_notification_preferences'],
            [$this->extended_context, $this->user_id]
        );
    }
}