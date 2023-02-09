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
defined('MOODLE_INTERNAL') || die();

final class rb_topic_usage_embedded extends rb_base_embedded {
    /**
     * @var string
     */
    public $defaultsortcolumn;

    /**
     * @var int
     */
    public $defaultsortorder;

    /**
     * rb_topic_usage_embedded constructor.
     */
    public function __construct() {
        $this->shortname = 'topic_usage';
        $this->url = "/totara/topic/usage.php";
        $this->source = 'topicusage';
        $this->fullname = get_string('usageoftopics', 'totara_topic');

        $this->defaultsortcolumn = 'topic_value';
        $this->defaultsortorder = SORT_ASC;

        $this->columns = [
            [
                'type' => 'topic',
                'value' => 'value',
                'heading' => get_string('topic', 'totara_topic')
            ],
            [
                'type' => 'usage',
                'value' => 'component',
                'heading' => get_string('component', 'totara_topic')
            ],
            [
                'type' => 'usage',
                'value' => 'total',
                'heading' => get_string('total', 'totara_topic')
            ]
        ];

        $this->filters = [
            [
                'type' => 'topic',
                'value' => 'value'
            ],
            [
                'type' => 'usage',
                'value' => 'component'
            ]
        ];

        parent::__construct();
    }

    /**
     * Check if the current viewer is able to view this report.
     *
     * @param int           $userid
     * @param reportbuilder $report
     *
     * @return bool
     */
    public function is_capable($userid, $report): bool {
        // Using context_system for now, as there are no use case and also tenant functionality
        // for specific tenant.
        $context = context_system::instance();
        return has_capability('totara/topic:report', $context, $userid);
    }

    /**
     * @return bool
     */
    public function embedded_global_restrictions_supported() {
        return true;
    }
}