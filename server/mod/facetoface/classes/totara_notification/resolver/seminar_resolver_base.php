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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\resolver;

use coding_exception;
use container_course\module\course_module;
use context_module;
use core\orm\query\builder;
use Exception;
use mod_facetoface\facilitator;
use mod_facetoface\facilitator_helper;
use mod_facetoface\messaging;
use mod_facetoface\room_helper;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\totara_notification\seminar_notification_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar_session_list;
use totara_core\extended_context;
use totara_notification\model\notification_preference;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\notifiable_event_resolver;

abstract class seminar_resolver_base extends notifiable_event_resolver implements permission_resolver, audit_resolver {

    /**
     * @inheritDocs
     * @throws coding_exception
     */
    public static function get_plugin_name(): ?string {
        return get_string('modulename', 'mod_facetoface');
    }

    /**
     * Returns the default delivery channels that defined for the event by developers.
     * However, note that admin can override this default delivery channels.
     *
     * If nothing/a specific channel is not listed here, it will fallback to the built in default.
     * To disable it, specify the actual default here.
     *
     * @return array
     */
    public static function get_notification_default_delivery_channels(): array {
        return [
            'email',
            'popup'
        ];
    }

    /**
     * Returns the extended context of where this event occurred. Note that this should almost certainly be
     * either the same as the natural context (but wrapped in the extended context container class) or an
     * extended context where the natural context is the immediate parent.
     *
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(
            context_module::instance($this->event_data['module_id']),
            'mod_facetoface',
            'seminar_event',
            $this->event_data['seminar_event_id']
        );
    }

    /**
     * Indicates whether the resolver supports the given context.
     * By default, resolvers support the system context.
     * Override this function to support other contexts.
     *
     * @param extended_context $extended_context
     * @return bool
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            if (in_array($context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_TENANT, CONTEXT_COURSECAT, CONTEXT_COURSE])) {
                return true;
            }

            if ($context->contextlevel == CONTEXT_MODULE) {
                $cm = course_module::from_id($context->instanceid);
                return $cm->get_modulename() == 'facetoface';
            }
        }

        return $context->contextlevel == CONTEXT_MODULE
            && $extended_context->get_area() == 'seminar_event'
            && $extended_context->get_component() == 'mod_facetoface';
    }

    /**
     * @param extended_context $context
     * @param int $user_id
     * @return bool
     * @throws coding_exception
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'moodle/course:managecoursenotifications';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'moodle/course:auditcoursenotifications';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @param notification_preference $preference
     * @return bool
     */
    public static function needs_icals(notification_preference $preference): bool {
        $raw_additional_criteria = $preference->get_additional_criteria();

        $additional_criteria = @json_decode(
            $raw_additional_criteria,
            true,
            32,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_BIGINT_AS_STRING
        );
        if (!is_array($additional_criteria)) {
            throw new Exception('json decoding failed');
        }

        if (!empty($additional_criteria['ical'])) {
            return in_array('include_ical_attachment', $additional_criteria['ical']);
        }

        return false;
    }

    /**
     * Get the ical attachments specific to the given facilitator.
     *
     * @param array $event_data
     * @param $user
     * @param int $method
     * @return array
     * @throws coding_exception
     */
    public static function get_facilitator_ical_attachments(array $event_data, $user, int $method): array {
        $records = builder::table(seminar_session::DBTABLE)
            ->as('sd')
            ->where('sd.sessionid', '=', $event_data['seminar_event_id'])
            ->join(['facetoface_facilitator_dates', 'fd'], 'fd.sessionsdateid', '=', 'sd.id')
            ->join([facilitator::DBTABLE, 'fac'], 'fac.id', '=', 'fd.facilitatorid')
            ->get()->all();
        $event_sessions = seminar_session_list::from_records($records);
        return self::get_ical_attachments($event_data, $user, $method, $event_sessions);
    }

    /**
     * @param array $event_data
     * @param $user
     * @param int $method
     * @param seminar_session_list|null $event_sessions
     * @param int $existing_attachments_count
     * @return array
     * @throws coding_exception
     */
    public static function get_ical_attachments(
        array $event_data, $user,
        int $method,
        seminar_session_list $event_sessions = null,
        int $existing_attachments_count = 0
    ): array {
        $seminar_event = (new seminar_event($event_data['seminar_event_id']));
        $seminar = $seminar_event->get_seminar();
        $session = $seminar_event->to_record();
        if (is_null($event_sessions)) {
            $event_sessions = $seminar_event->get_sessions();
        }
        $attachments = [];
        $count = $existing_attachments_count + 1;

        // Sort event sessions by timestart ASC which the sort does by default.
        $event_sessions = $event_sessions->sort('timestart');

        /** @var seminar_session $event_session */
        foreach ($event_sessions as $event_session) {
            // Generate_ical needs session dates on the session stdClass object.
            $session->sessiondates = (object)[
                'id' => $event_session->get_id(),
                'sessionid' => $event_session->get_sessionid(),
                'sessiontimezone' => $event_session->get_sessiontimezone(),
                'timestart' => $event_session->get_timestart(),
                'timefinish' => $event_session->get_timefinish(),
                'roomids' => room_helper::get_room_ids_sorted($event_session->get_id()),
                'facilitatorids' => facilitator_helper::get_facilitator_ids_sorted($event_session->get_id()),
            ];

            $ical = messaging::generate_ical(
                (object)[
                    'id' => $seminar->get_id(),
                    'name' => $seminar->get_name(),
                    'intro' => $seminar->get_intro(),
                ],
                $session,
                $method,
                $user,
                null,
                []
            );

            $ical_content = $ical->content;
            $ical_uids = null;
            $ical_method = '';

            if (!empty($ical_content)) {
                preg_match_all('/UID:([^\r\n ]+)/si', $ical_content, $matches);
                $ical_uids = $matches[1];
                preg_match('/METHOD:([a-z]+)/si', $ical_content, $matches);
                $ical_method = $matches[1];
            }

            $attachments[] = [
                'attachname' => 'Session' . $count++ . '.ics',
                'attachment' => $ical->file,
                'ical_uids' => $ical_uids,
                'ical_method' => $ical_method,
            ];
        }

        return $attachments;
    }

    /*
    * Override to return an array of strings letting users (notif admins) know that there is a problem with
    * some configuration which will occur if this resolver is used in this context. For example, if some
    * functionality is disabled which means that notifications will never be fired from this resolver.
    *
    * @param extended_context $extended_context
    * @return string[]
    */
    public static function get_warnings(extended_context $extended_context): array {
        $warnings = parent::get_warnings($extended_context);

        if ($extended_context->is_natural_context() && $extended_context->get_context_level() == CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('facetoface', $extended_context->get_context()->instanceid);
            $seminar = new seminar($cm->instance);

            if (!seminar_notification_helper::use_cn_notifications($seminar)) {
                $warnings[] = get_string('centralisednotifications_disabled', 'mod_facetoface');
            }
        }

        return $warnings;
    }
}