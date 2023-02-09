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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_api
 */

/**
 * Behat steps to provide additional functionality for api related tasks.
 */
class behat_totara_api extends behat_base {

    /**
     * Check that the API documentation is built
     *
     * @Given the API documentation has been built
     */
    public function the_api_documentation_is_built() {
        \behat_hooks::set_step_readonly(true);
        if (empty(\totara_api\views\documentation_view::built_asset_files())) {
            throw new \Moodle\BehatExtension\Exception\SkippedException('Documentation assets not built');
        }
    }

    /**
     * Navigate to the API documentation page.
     *
     * @Given I am on the API documentation page
     */
    public function i_am_on_the_api_documentation_page() {
        \behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/api/documentation/index.php');
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();

        // Ignore 'Access denied' errors as we want to test for that in the feature.
        $this->execute('behat_general::i_ignore_exception_in_log', ['Access denied']);
    }

    /**
     * Navigate to the API settings page.
     *
     * @Given I am on the API settings page
     */
    public function i_am_on_the_api_settings_page() {
        \behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/api/documentation/index.php');
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();

        // Ignore 'Access denied' errors as we want to test for that in the feature.
        $this->execute('behat_general::i_ignore_exception_in_log', ['Access denied']);
    }

    /**
     * @Given I move user :username to tenant :tenant_id_number
     *
     * @param string $username
     * @param string $tenant_id_number
     * @return void
     */
    public function i_update_user_tenant(string $username, string $tenant_id_number): void {
        global $DB;

        \behat_hooks::set_step_readonly(false);

        $user = \core_user::get_user_by_username($username);
        $tenant = \core\entity\tenant::repository()->where('idnumber', $tenant_id_number)->one(true);
        $user->tenantid = $tenant->id;

        $DB->update_record('user', $user);
    }

}