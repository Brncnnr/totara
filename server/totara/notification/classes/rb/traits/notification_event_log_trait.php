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
use rb_filter_option;
use rb_join;
use totara_notification\factory\notifiable_event_resolver_factory;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait notification_event_log_trait
 */
trait notification_event_log_trait {
    protected ?string $notification_event_log_join = null;

    /**
     * Add notification event log info where notification_event_log is the base table.
     *
     * @throws coding_exception
     */
    protected function add_notification_event_log_to_base() {
        /** @var notification_event_log_trait|rb_base_source $this */
        if (isset($this->notification_event_log_join)) {
            throw new coding_exception('Notification Event Log info can be added only once!');
        }

        $this->notification_event_log_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_notification', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_notification';
        }

        $this->add_notification_event_log_joins();
        $this->add_notification_event_log_columns();
        $this->add_notification_event_log_filters();
    }

    /**
     * Add notification event log info.
     * If a new join isn't specified then the existing join will be used.
     *
     * @param rb_join|null $join
     * @throws coding_exception
     */
    protected function add_notification_event_log(rb_join $join = null): void {
        $join = $join ?? $this->get_join('notification_event_log');

        /** @var notification_event_log_trait|rb_base_source $this */
        if (isset($this->notification_event_log_join)) {
            throw new coding_exception('Notification event log info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->notification_event_log_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('totara_notification', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'totara_notification';
        }

        $this->add_notification_event_log_joins();
        $this->add_notification_event_log_columns();
        $this->add_notification_event_log_filters();
    }

    /**
     * Add column options for notification event log to report.
     */
    protected function add_notification_event_log_columns(): void {
        /** @var notification_event_log_trait|rb_base_source $this */
        $join = $this->notification_event_log_join;

        $this->columnoptions[] = new rb_column_option(
            'notification_event_log',
            'context_id',
            '', // The hidden column does not require the string name.
            "{$join}.context_id",
            [
                'joins' => [$join],
                'displayfunc' => 'integer',
                'selectable' => false

            ]
        );
        $this->columnoptions[] = new rb_column_option(
            'notification_event_log',
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
            'notification_event_log',
            'item_id',
            '', // The hidden column does not require the string name.
            "{$join}.item_id",
            [
                'joins' => [$join],
                'displayfunc' => 'integer',
                'selectable' => false
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'notification_event_log',
            'event_time',
            get_string('event_time', 'rb_source_notification_event_log'),
            "{$join}.time_created",
            [
                'joins' => [$join],
                'displayfunc' => 'nice_datetime'
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'notification_event_log',
            'event_name',
            get_string('event_name', 'rb_source_notification_event_log'),
            "{$join}.display_string_key",
            [
                'joins' => [$join],
                'displayfunc' => 'notification_event_log_event_name',
                'extrafields' => [
                    'resolver_class_name' => "{$join}.resolver_class_name",
                    'string_params' => "{$join}.display_string_params"
                ],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'notification_event_log',
            'schedule',
            get_string('schedule', 'rb_source_notification_event_log'),
            "{$join}.schedule_offset",
            [
                'joins' => [$join],
                'displayfunc' => 'notification_event_log_schedule'
            ]
        );

        $this->add_core_user_columns($this->columnoptions, 'subject_user', 'subject_user', true);
    }

    /**
     * Add joins required for notification event log column and filter options to report.
     */
    protected function add_notification_event_log_joins(): void {
        global $DB;

        /** @var notification_event_log_trait|rb_base_source $this */
        $join = $this->notification_event_log_join;

        $this->joinlist[] = new rb_join(
            'event_context',
            'INNER',
            '{context}',
            $join . ".context_id = event_context.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join]
        );

        $this->joinlist[] = new rb_join(
            'current_context',
            'INNER',
            '{context}',
            $DB->sql_concat('event_context.path', "'/'") . ' LIKE ' . $DB->sql_concat('current_context.path', "'/%'"),
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            ['event_context']
        );

        $this->add_core_user_tables(
            $this->joinlist,
            $join,
            'subject_user_id',
            'subject_user',
        );
    }

    /**
     * Add filter options for notification event log to report.
     */
    protected function add_notification_event_log_filters(): void {
        global $PAGE;

        // It's a tad hacky but check for a page context.
        $context_id = $PAGE->context->id ?? \context_system::instance()->id;

        $join = $this->notification_event_log_join;

        $this->filteroptions[] = new rb_filter_option(
            'notification_event_log',
            'event_time',
            get_string('event_time', 'rb_source_notification_event_log'),
            'date'
        );

        // Get some info for the resolver class filter.
        $select_width_options = rb_filter_option::select_width_limiter();
        $resolver_classes = notifiable_event_resolver_factory::get_context_available_resolvers($context_id);

        // Quick transformation to make the data filter friendly.
        $options = [];
        foreach ($resolver_classes as $resolver_class) {
            $options[$resolver_class] = call_user_func([$resolver_class, 'get_notification_title']);
        }

        // Thats all the setup, now add the filter.
        $this->filteroptions[] = new rb_filter_option(
            'notification_event_log',
            'resolver_class_name',
            get_string('resolver_name', 'rb_source_notification_event_log'),
            'select',
            array(
                'selectchoices' => $options,
                'attributes' => $select_width_options,
            ),
            "{$join}.resolver_class_name"
        );

        $this->add_core_user_filters($this->filteroptions, 'subject_user', true);
    }
}
