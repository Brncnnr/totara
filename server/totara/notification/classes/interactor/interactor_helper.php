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

use totara_core\extended_context;

class interactor_helper {
    /**
     * @param extended_context $extended_context
     * @param string $resolver_class
     * @param int $user_id
     * @return array
     */
    public static function get_resolver_interaction_in_context(extended_context $extended_context, string $resolver_class, int $user_id): array {
        $pref_iterator = new notification_preference_interactor($extended_context, $user_id);
        $audit_iterator = new notification_audit_interactor($extended_context, $user_id);
        return [
            'can_manage' => $pref_iterator->can_manage_notification_preferences_of_resolver($resolver_class),
            'can_audit' => $audit_iterator->can_audit_notifications_of_resolver($resolver_class),
        ];
    }
}