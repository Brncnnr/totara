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
 * @package totara_engage
 */

use Behat\Gherkin\Node\TableNode;
use core_user\totara_engage\share\recipient\user as user_recipient;

/**
 * Behat steps to generate engage related data.
 *
 */
class behat_totara_engage extends behat_base {
    /**
     * @return \totara_engage\testing\generator
     */
    protected function get_data_generator(): \totara_engage\testing\generator {
        return \totara_engage\testing\generator::instance();
    }

    /**
     * Create shares.
     *
     * @Given :component :name is shared with the following users:
     * @param string $component
     * @param string $name
     * @param TableNode $node
     */
    public function is_shared_with_the_following_users(string $component, string $name, TableNode $node) {
        // Get generator.
        $gen = $this->get_data_generator();

        // Get table with values.
        $table = $node->getTable();
        $columns = $table[0];

        // Get directory for component.
        $plugin = explode('_', $component);
        $directory = core_component::get_plugin_directory($plugin[0], $plugin[1]);

        if (!$directory) {
            throw new coding_exception("Plugin directory not found for '{$component}'");
        }

        // Include plugin behat.
        $class = "behat_{$component}";
        $file = $directory . "/tests/behat/{$class}.php";
        include_once($file);

        $function = "$class::get_item_by_name";
        $item = call_user_func($function, $name);

        // Get all recipients.
        $shares = [];
        $rows = array_slice($table, 1);
        foreach ($rows as $row) {
            $row = array_combine($columns, $row);
            $sharer = core_user::get_user_by_username($row['sharer']);
            $recipient = core_user::get_user_by_username($row['recipient']);
            $shares[$sharer->id][] = new user_recipient($recipient->id);
        }

        // Create shares.
        foreach ($shares as $sharerid => $recipients) {
            $gen->share_item($item, $sharerid, $recipients);
        }
    }

}