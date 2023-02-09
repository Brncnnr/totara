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

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\totara_notification\resolver\booking_confirmed;
use totara_core\extended_context;
use totara_notification\model\notification_event_log;
use totara_notification\rb\traits\notification_event_log_trait;

class rb_source_notification_event_log extends rb_base_source {

    use notification_event_log_trait;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->usedcomponents[] = 'totara_notification';
        $this->base = '{notification_event_log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        $this->add_notification_event_log_to_base();

        $this->sourcetitle   = $this->define_sourcetitle();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();

        $this->sourcesummary = get_string('source_summary', 'rb_source_notification_event_log');
        $this->sourcelabel = get_string('source_label', 'rb_source_notification_event_log');

        parent::__construct();
    }

    /**
     * @return string
     */
    protected function define_sourcetitle(): string {
        return get_string('source_title', 'rb_source_notification_event_log');
    }

    /**
     * @return rb_column_option[]
     */
    protected function define_columnoptions(): array {
        return [
            new rb_column_option(
                'notification_event_log',
                'notification_event_status',
                get_string('notification_event_status', 'rb_source_notification_event_log'),
                "base.has_error",
                [
                    'displayfunc' => 'notification_event_log_status',
                    'extrafields' => [
                        'notified_successfully' => "(SELECT COUNT(id) FROM {notification_log}
                                                    WHERE notification_event_log_id = base.id
                                                    AND has_error = 0)",
                        'notifications' => "(SELECT COUNT(id) FROM {notification_log} WHERE notification_event_log_id = base.id)",
                        'descendant_has_error' => "(SELECT CASE WHEN COUNT(nl.id) >= 1 THEN 1 ELSE 0 END
                                                    FROM {notification_log} AS nl
                                                    LEFT JOIN {notification_delivery_log} AS ndl ON ndl.notification_log_id = nl.id
                                                    WHERE nl.notification_event_log_id = base.id
                                                    AND (ndl.has_error = 1 OR nl.has_error = 1))"
                    ]

                ]
            ),
            new rb_column_option(
                'notification_event_log',
                'event_has_error',
                get_string('event_has_error', 'rb_source_notification_event_log'),
                'base.has_error',
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                ]
            ),
            new rb_column_option(
                'notification_event_log',
                'descendant_has_error',
                get_string('descendant_has_error', 'rb_source_notification_event_log'),
                "(SELECT CASE WHEN COUNT(nl.id) >= 1 THEN 1 ELSE 0 END
                        FROM {notification_log} AS nl
                        LEFT JOIN {notification_delivery_log} AS ndl ON ndl.notification_log_id = nl.id
                        WHERE nl.notification_event_log_id = base.id
                        AND (ndl.has_error = 1 OR nl.has_error = 1))",
                [
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                ]
            ),
            new rb_column_option(
                'notification_event_log',
                'notification_loglink',
                get_string('notification_loglink', 'rb_source_notification_event_log'),
                'base.id',
                [
                    'displayfunc' => 'notification_log_link',
                    'noexport' => true,
                ]
            ),
        ];
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_defaultcolumns(): array {
        return self::get_default_columns();
    }

    /**
     * @return rb_join[]
     */
    protected function define_joinlist(): array {
        return [];
    }

    /**
     * @return rb_param_option[]
     */
    protected function define_paramoptions(): array {
        return [
            new rb_param_option(
                'user_id', // Parameter name.
                'subject_user.id'  // Field.
            ),
            new rb_param_option(
                'context_id',
                'current_context.id',
                'current_context'
            ),
            new rb_param_option(
                'component',
                'base.component'
            ),
            new rb_param_option(
                'area',
                'base.area'
            ),
            new rb_param_option(
                'item_id',
                'base.item_id'
            ),
            new rb_param_option(
                'resolver_class_name',
                'base.resolver_class_name'
            ),
        ];
    }

    /**
     * @return array[]
     */
    protected function define_defaultfilters(): array {
        return self::get_default_filters();
    }

    /**
     * @return rb_filter_option[]
     */
    protected function define_filteroptions(): array {
        return [
            new rb_filter_option(
                'notification_event_log',
                'event_has_error',
                get_string('event_has_error', 'rb_source_notification_event_log'),
                'select',
                [
                    'selectfunc' => 'yesno_list'
                ]
            ),
        ];
    }

    /**
     * The default filters for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_filters(): array {
        return [
            [
                'type' => 'subject_user',
                'value' => 'fullname',
                'advanced' => 0,
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'event_has_error',
                'advanced' => 0,
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'resolver_class_name',
                'advanced' => 0,
            ],
        ];
    }

    /**
     * Global report restrictions are implemented in this source.
     *
     * @return boolean
     */
    public function global_restrictions_supported(): bool {
        return true;
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions(): array {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'subject_user');

        return $contentoptions;
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns(): array {
        // Be aware that if you change these, it'll affect the embedded report.
        return [
            [
                'type' => 'notification_event_log',
                'value' => 'event_time',
                'heading' => get_string('event_time', 'rb_source_notification_event_log'),
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'event_name',
                'heading' => get_string('event_name', 'rb_source_notification_event_log'),
            ],
            [
                'type' => 'subject_user',
                'value' => 'namelink',
                'heading' => get_string('subject_user', 'rb_source_notification_event_log'),
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'schedule',
                'heading' => get_string('schedule', 'rb_source_notification_event_log'),
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'notification_event_status',
                'heading' => get_string('notification_event_status', 'rb_source_notification_event_log'),
            ],
            [
                'type' => 'notification_event_log',
                'value' => 'notification_loglink',
                'heading' => get_string('notification_loglink', 'rb_source_notification_event_log'),
            ]
        ];
    }

    /**
     * Inject column_test data into database.
     *
     * @codeCoverageIgnore
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }
        // Nothing to do by default.
        $generator = \core\testing\generator::instance();
        $user = $generator->create_user(['lastname' => 'User1 last name']);
        $context = context_system::instance();
        $extended_context = extended_context::make_with_context(
            $context,
            'totara_notification',
            'seminar',
            1
        );

        notification_event_log::create(
            booking_confirmed::class,
            $extended_context,
            $user->id,
            [],
            '',
            '',
            '',
            [],
            false
        );
    }
}
