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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\entity\notification_preference;

/**
 * Behat steps to generate notification related data.
 */
class behat_totara_notification extends behat_base {
    /**
     * Goes to the notifications page.
     *
     * @Given I navigate to system notifications page
     */
    public function i_navigate_to_system_notifications_page() {
        behat_hooks::set_step_readonly(false);

        // Go directly to URL, we are testing functionality of page, not how to get there.
        $url = new moodle_url("/totara/notification/notifications.php");
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * I navigate to the notifications page of the component with a unique name.
     * @Given /^I navigate to notifications page of "([^"]*)" "([^"]*)"$/
     * @param string $component
     * @param string $unique_name
     *
     * @return void
     */
    public function i_navigate_to_notifications_page_of(string $component, string $unique_name): void {
        global $DB;

        behat_hooks::set_step_readonly(true);
        $context_id = null;

        switch ($component) {
            case 'course':
                $course_id = $DB->get_field(
                    'course',
                    'id',
                    ['shortname' => $unique_name],
                    MUST_EXIST
                );

                $context_id = context_course::instance($course_id)->id;
                break;

            default:
                throw new coding_exception(
                    "Unsupported component '{$component}' for fetching the context's id for notification page"
                );
        }

        $url = new moodle_url(
            '/totara/notification/context_notifications.php',
            ['context_id' => $context_id]
        );

        $this->getSession()->visit(
            $this->locate_path($url->out_as_local_url(false))
        );

        $this->wait_for_pending_js();
    }

    /**
     * The notification preference is set to enabled.
     *
     * @Given /^the notification preference "([^"]*)" is set to enabled$/
     * @param string $notification_class_name
     *
     * @return void
     */
    public function the_notification_preference_is_set_to_enabled(string $notification_class_name): void {
        behat_hooks::set_step_readonly(false);

        notification_preference::repository()
            ->where('notification_class_name', $notification_class_name)
            ->update(['enabled' => 1]);
    }
}