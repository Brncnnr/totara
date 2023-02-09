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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

use totara_core\advanced_feature;

/**
 * Class block_totara_recently_viewed
 *
 * Recent block shows the recently viewed
 */
class block_totara_recently_viewed extends block_base {
    const COMPONENT = 'block_totara_recently_viewed';

    /**
     * Initialise the block
     */
    function init() {
        $this->title = get_string('title', self::COMPONENT);
        $this->version = 2020060800;
    }

    /**
     * Multiple instances are acceptable, if the feature is on.
     * This allows us to prevent new instances from being added
     */
    function instance_allow_multiple() {
        return advanced_feature::is_enabled('ml_recommender');
    }

    function get_required_javascript() {
        parent::get_required_javascript();
        $arguments = [
            'blockid' => $this->instance->id,
        ];
        $this->page->requires->js_call_amd(self::COMPONENT . '/resize_blocks', 'init', $arguments);
    }

    /**
     * @return stdClass|string|null
     */
    function get_content() {
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        if (advanced_feature::is_disabled('ml_recommender')) {
            return '';
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        /** @var \stdClass $config */
        $config = $this->config;

        /** @var block_totara_recently_viewed_renderer $renderer */
        $renderer = $this->page->get_renderer(self::COMPONENT);

        $this->content->text = $renderer->render_cards($config, $this->instance->id);

        return $this->content;
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    function user_can_addto($page) {
        // If there are no blocks on the page, then prevent addition.
        // If there are blocks on the page, we have to let addition happen (it's prevented by the allow_multiple option).
        // This is done as deletes are blocked by this check
        if (advanced_feature::is_disabled('ml_recommender') && !$page->blocks->is_block_present('totara_recently_viewed')) {
            return false;
        }

        return parent::user_can_addto($page);
    }
}
