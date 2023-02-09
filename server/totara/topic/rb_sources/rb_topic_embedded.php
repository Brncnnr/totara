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

final class rb_topic_embedded extends rb_base_embedded {
    /**
     * @var string
     */
    public $defaultsortcolumn;

    /**
     * @var int
     */
    public $defaultsortorder;

    /**
     * rb_topic_embedded constructor.
     */
    public function __construct() {
        $this->url = "/totara/topic/index.php";
        $this->source = "topic";
        $this->shortname = 'topic';
        $this->fullname = get_string('managetopics', 'totara_topic');

        $this->defaultsortcolumn = "topic_value";
        $this->defaultsortorder = SORT_ASC;

        $this->columns = [
            [
                'type' => 'topic',
                'value' => 'value',
                'heading' => get_string('topic', 'totara_topic')
            ],
            [
                'type' => 'topic',
                'value' => 'timemodified',
                'heading' => get_string('timemodified', 'totara_topic')
            ],
            [
                'type' => 'topic',
                'value' => 'actions',
                'heading' => get_string('actions', 'rb_source_topic')
            ]
        ];

        $this->filters = [];
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        parent::__construct();
    }

    /**
     * @return bool
     */
    public function embedded_global_restrictions_supported(): bool {
        return true;
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
}