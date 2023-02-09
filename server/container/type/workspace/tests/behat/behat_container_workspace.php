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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */

use Behat\Gherkin\Node\TableNode;
use container_workspace\totara_engage\share\recipient\library as library_recipient;
use container_workspace\workspace;

/**
 * Behat steps to generate engage related data.
 *
 */
class behat_container_workspace extends behat_base {
    /**
     * @return \totara_engage\testing\generator
     */
    protected function get_engage_data_generator(): \totara_engage\testing\generator {
        return \totara_engage\testing\generator::instance();
    }

    /**
     * Create shares.
     *
     * @Given the following is shared with workspaces:
     * @param TableNode $node
     */
    public function the_following_is_shared_with_workspaces(TableNode $node) {
        // Get generator.
        $gen = $this->get_engage_data_generator();

        // Get table with values.
        $table = $node->getTable();
        $columns = $table[0];

        $shares = [];
        $rows = array_slice($table, 1);
        foreach ($rows as $row) {
            $row = array_combine($columns, $row);

            // Get directory for component.
            [$plugin_type, $plugin_name] = core_component::normalize_component($row['component']);
            $directory = core_component::get_plugin_directory($plugin_type, $plugin_name);

            if (!$directory || !file_exists($directory)) {
                throw new coding_exception("Plugin directory not found for '{$row['component']}'");
            }

            // Include plugin behat.
            $class = "behat_{$row['component']}";
            $file = $directory . "/tests/behat/{$class}.php";
            include_once($file);

            $function = "$class::get_item_by_name";
            $item = call_user_func($function, $row['name']);

            $sharer = core_user::get_user_by_username($row['sharer']);
            $recipient = self::get_workspace_by_name($row['workspace_name']);

            $shares[] = [
                'item' => $item,
                'sharer' => $sharer,
                'recipient' => new library_recipient($recipient->id)
            ];
        }

        // Create shares.
        foreach ($shares as $share) {
            $sharer = $share['sharer'];
            $gen->share_item($share['item'], $sharer->id, [$share['recipient']]);
        }
    }

    /**
     * @When /^I access the "([^"]*)" workspace$/
     * @param string $workspace_name
     */
    public function i_access_the_workspace(string $workspace_name): void {
        behat_hooks::set_step_readonly(false);
        $workspace = $this->get_workspace_by_name($workspace_name);
        $workspace_url = $workspace->get_workspace_url();
        $this->getSession()->visit($this->locate_path($workspace_url->out(false)));
    }

    /**
     * @When /^I access the workspace by id "([^"]*)"$/
     * @param int $id
     */
    public function i_access_the_workspace_by_id(int $id): void {
        behat_hooks::set_step_readonly(false);
        $workspace_url = workspace::create_url($id);
        $this->getSession()->visit($this->locate_path($workspace_url->out(false)));
    }

    /**
     * @When /^I access the discussion by id "([^"]*)"$/
     * @param int $id
     */
    public function i_access_the_discussion_by_id(int $id): void {
        behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path("/container/type/workspace/discussion.php?id={$id}"));
    }

    /**
     * @param string $name
     * @return workspace
     */
    public static function get_workspace_by_name(string $name): workspace {
        global $DB;
        $workspace_id = $DB->get_field('course', 'id', [
            'fullname' => $name,
            'category' => workspace::get_default_category_id(),
            'containertype' => workspace::get_type(),
        ]);
        return workspace::from_id($workspace_id);
    }

}