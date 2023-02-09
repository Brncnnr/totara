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
 * @package engage_article
 */

use engage_article\totara_engage\resource\article;
use totara_engage\entity\engage_resource;
use totara_engage\resource\resource_item;

/**
 * Behat steps to generate article related data.
 */
class behat_engage_article extends behat_base {

    /**
     * Goes to the article view page.
     *
     * @Given I view article :name
     * @param string $name
     */
    public function i_view_article(string $name): void {
        global $DB;

        \behat_hooks::set_step_readonly(false);

        $resource_id = $DB->get_field('engage_resource', 'id', [
            'name' => $name,
            'resourcetype' => 'engage_article'
        ]);

        // Go directly to URL, we are testing functionality of page, not how to get there.
        $url = new moodle_url("/totara/engage/resources/article/index.php?id={$resource_id}");
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * @param string $name
     * @return resource_item
     */
    public static function get_item_by_name(string $name): resource_item {
        global $DB;
        $resourceid = $DB->get_field(engage_resource::TABLE, 'id', ['name' => $name]);
        return article::from_resource_id($resourceid);
    }
}