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
 * @package totara_playlist
 */

use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\playlist;
use Behat\Gherkin\Node\TableNode;

/**
 * Behat steps to generate playlist related data.
 */
class behat_totara_playlist extends behat_base {
    /**
     * Goes to the playlist view page.
     *
     * @Given I view playlist :name
     * @param $name
     */
    public function i_view_playlist($name) {
        global $DB;

        \behat_hooks::set_step_readonly(false);
        $playlist_id = $DB->get_field('playlist', 'id', ['name' => $name]);

        // Go directly to URL, we are testing functionality of page, not how to get there.
        $url = new moodle_url("/totara/playlist/index.php?id={$playlist_id}");
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * @Given I rate the playlist :rating
     * @param $rating
     */
    public function i_rate_playlist(int $rating) {
        \behat_hooks::set_step_readonly(false);

        $nodes = $this->find_all("xpath", "//div[@class='tui-popoverFrame__content']//div[@class='tui-engageStarRating']//*[local-name()='svg']");

        if ($rating > 4) {
            $rating = 4;
        } else if ($rating < 0) {
            $rating = 0;
        }

        $nodes[$rating]->click();
    }

    /**
     * @param string $name
     * @return playlist
     */
    public static function get_item_by_name(string $name): playlist {
        global $DB;
        $playlistid = $DB->get_field(playlist_entity::TABLE, 'id', ['name' => $name]);
        return playlist::from_id($playlistid, false);
    }

}