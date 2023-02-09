<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic;

/**
 * A helper class to inject all the settings needed into totara system.
 */
final class setting {
    /**
     * Start adding settings page/node for topics.
     *
     * @param \admin_root $root
     * @return void
     */
    public static function initialise_settings(\admin_root $root): void {
        // Adding external page for managing topics
        $root->add(
            'root',
            new \admin_category(
                'topic',
                get_string('topic', 'totara_topic')
            )
        );

        // Manage topic node
        $root->add(
            'topic',
            new \admin_externalpage(
                'managetopics',
                new \lang_string('managetopics', 'totara_topic'),
                new \moodle_url("/totara/topic/index.php"),
                'totara/topic:config'
            )
        );

        // View topic usage node
        $root->add(
            'topic',
            new \admin_externalpage(
                'topicusage',
                new \lang_string('usageoftopics', 'totara_topic'),
                new \moodle_url("/totara/topic/usage.php"),
                'totara/topic:config'
            )
        );
    }
}