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

use totara_core\extended_context;
use totara_notification\factory\notifiable_event_resolver_factory;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/notification/rb_sources/rb_source_notification_delivery_log.php');

class rb_notification_delivery_log_embedded extends rb_base_embedded {
    /**
     * @var string {report_builder}.defaultsortcolumn
     */
    public $defaultsortcolumn = '';

    /**
     * @param $data
     */
    public function __construct($data) {
        $this->url = '/totara/notification/notification_delivery_log.php';
        $this->source = 'notification_delivery_log';
        $this->shortname = 'notification_delivery_log';
        $this->fullname = get_string('notification_delivery_log_embedded', 'rb_source_notification_delivery_log');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        $this->defaultsortcolumn = 'event_date';

        if (isset($data['context_id']) && (int)$data['context_id']) {
            $extended_context = extended_context::make_with_id(
                $data['context_id'],
                $data['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
                $data['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
                $data['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID,
            );
        } else {
            $extended_context = extended_context::make_system();
        }

        // set nothing for natural system context
        if (!$extended_context->is_natural_context()) {
            $this->embeddedparams['context_id'] = $extended_context->get_context_id();

            if ($extended_context->get_area()
                && $extended_context->get_component()
                && $extended_context->get_item_id()) {
                $this->embeddedparams['area'] = $extended_context->get_area();
                $this->embeddedparams['component'] = $extended_context->get_component();
                $this->embeddedparams['item_id'] = $extended_context->get_item_id();
            }
        } else if (!$extended_context->is_same(extended_context::make_system())) {
            $this->embeddedparams['context_id'] = $extended_context->get_context_id();
        }

        if (isset($data['user_id']) && (int)$data['user_id'] > 0) {
            $this->embeddedparams['user_id'] = $data['user_id'];
        }

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;

        $this->contentsettings = array(
            'user_visibility' => array(
                'enable' => 1,
            )
        );

        parent::__construct();
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_columns(): array {
        return rb_source_notification_delivery_log::get_default_columns();
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_filters(): array {
        return rb_source_notification_delivery_log::get_default_filters();
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     *
     * @return boolean
     */
    public function embedded_global_restrictions_supported(): bool {
        return true;
    }

    /**
     * Can searches be saved?
     *
     * @return bool
     */
    public static function is_search_saving_allowed(): bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report): bool {
        $context_id = $report->get_param_value('context_id') ?? null;

        // The report should be able to access from the course context as well. So it must not be tight for the system context.
        if ($user_id = $this->embeddedparams['user_id'] ?? false) {
            $user_context = context_user::instance($user_id);
            $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context, $reportfor);

            // When the target user is the same as the log-in user, then log-in user should have either
            // the 'auditownnotifications' or 'auditnotifications' capability see the logs.
            if ($user_id == $reportfor) {
                $audit_own_notification = has_capability('totara/notification:auditownnotifications', $user_context, $reportfor);

                return $audit_own_notification || $audit_all_notification;
            }

            return $audit_all_notification;
        }

        if ($context_id) {
            $context = context::instance_by_id($context_id);
        } else {
            $context = context_system::instance();
        }

        // Capabilities can only be granted in real contexts, so no need to get the extended parts of the context.
        $extended_context = extended_context::make_with_context($context);
        return notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($extended_context, $reportfor, true);
    }
}