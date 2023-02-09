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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\rb\traits;

use coding_exception;
use rb_base_source;
use rb_column_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait notification_log_trait
 */
trait notification_log_trait {
    /** @var string $notification_log_join */
    protected $notification_log_join = null;

    /**
     * Add notification log info where notification_log is the base table.
     *
     * @throws coding_exception
     */
    protected function add_notification_log_to_base() {
        /** @var notification_log_trait|rb_base_source $this */
        if (isset($this->notification_log_join)) {
            throw new coding_exception('Notification Log info can be added only once!');
        }

        $this->notification_log_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_notification', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_notification';
        }

        $this->add_notification_log_joins();
        $this->add_notification_log_columns();
        $this->add_notification_log_filters();
    }

    /**
     * Add notification log info.
     * If a new join isn't specified then the existing join will be used.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_notification_log(rb_join $join = null): void {
        $join = $join ?? $this->get_join('notification_log');

        /** @var notification_log_trait|rb_base_source $this */
        if (isset($this->notification_log_join)) {
            throw new coding_exception('Notification log info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->notification_log_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_notification', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_notification';
        }

        $this->add_notification_log_joins();
        $this->add_notification_log_columns();
        $this->add_notification_log_filters();
    }

    /**
     * Add joins required for notification log column and filter options to report.
     */
    protected function add_notification_log_joins() {
        /** @var notification_log_trait|rb_base_source $this */
        $join = $this->notification_log_join;

        $this->add_core_user_tables(
            $this->joinlist,
            $join,
            'recipient_user_id',
            'recipient_user'
        );

        $this->joinlist[] = new rb_join(
            'notification_event_log',
            'INNER',
            '{notification_event_log}',
            "{$join}.notification_event_log_id = notification_event_log.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join]
        );
    }

    /**
     * Add column options for notification log to report.
     */
    protected function add_notification_log_columns(): void {
        /** @var notification_log_trait|rb_base_source $this */
        $join = $this->notification_log_join;

        $this->columnoptions[] = new rb_column_option(
            'notification_log',
            'id',
            '', // The hidden column does not require the string name.
            "{$join}.id",
            [
                'joins' => [$join],
                'displayfunc' => 'integer',
                'selectable' => false
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'notification_log',
            'preference_title',
            get_string('preference_title', 'rb_source_notification_log'),
            "{$join}.preference_id",
            [
                'joins' => [$join],
                'displayfunc' => 'notification_log_preference_title'
            ]
        );

        $this->add_core_user_columns($this->columnoptions, 'recipient_user', 'recipient_user', true);
    }

    /**
     * Add filter options for notification log to report.
     */
    protected function add_notification_log_filters() {
        $this->add_core_user_filters($this->filteroptions, 'recipient_user', true);
    }
}