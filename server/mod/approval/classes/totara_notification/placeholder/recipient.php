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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */
namespace mod_approval\totara_notification\placeholder;

use coding_exception;
use core_user\totara_notification\placeholder\user;
use html_writer;
use mod_approval\controllers\application\dashboard as dashboard_controller;
use mod_approval\controllers\application\pending as pending_controller;
use moodle_url;
use totara_notification\placeholder\option;

class recipient extends user {
    /**
     * @return option[]
     */
    public static function get_options(): array {
        $options = parent::get_options();

        $options[] = option::create(
            'application_dashboard',
            get_string('notification_placeholder:recipient_application_dashboard_label', 'mod_approval')
        );
        $options[] = option::create(
            'approval_actions',
            get_string('notification_placeholder:recipient_approval_actions_label', 'mod_approval')
        );

        return $options;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if (null === $this->entity) {
            throw new coding_exception("The user entity record is empty");
        }

        switch ($key) {
            case 'application_dashboard':
                return html_writer::link(
                    new moodle_url(dashboard_controller::get_base_url()),
                    get_string('notification_placeholder:recipient_application_dashboard', 'mod_approval')
                );
            case 'approval_actions':
                return html_writer::link(
                    new moodle_url(pending_controller::get_base_url()),
                    get_string('notification_placeholder:recipient_approval_actions', 'mod_approval')
                );
            default:
                return parent::do_get($key);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('application_dashboard' === $key) {
            return true;
        }

        if ('approval_actions' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }
}