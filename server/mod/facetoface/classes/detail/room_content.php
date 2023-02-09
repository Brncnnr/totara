<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\detail;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context;
use context_system;
use core\entity\user;
use core\output\notification;
use mod_facetoface\output\helper\virtualroom_card_factory;
use mod_facetoface\output\seminarresource_card;
use mod_facetoface\output\virtualroom_card;
use moodle_url;
use mod_facetoface_renderer;
use rb_facetoface_summary_room_embedded;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_attachment_item;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;

/**
 * Generate room details.
 */
class room_content extends content_generator {
    /**
     * Constructor.
     * @param string $idparam a parameter name that represents 'id'
     * @param string|moodle_url $pageurl the URL to this page
     */
    public function __construct(string $idparam, $pageurl) {
        parent::__construct($idparam, 'modfacetofacerooms', 'facetoface_summary_room', $pageurl);
    }

    protected function load(int $id): seminar_attachment_item {
        return new room($id);
    }

    protected function get_title(seminar_attachment_item $item): string {
        /** @var room $item */
        return $item->get_name();
    }

    protected function has_edit_capability(seminar_attachment_item $item, context $context, stdClass $user): bool {
        // The managesitewide capability is always system level.
        return has_capability('mod/facetoface:managesitewiderooms', context_system::instance(), $user);
    }

    protected function has_report_capability(seminar_attachment_item $item, context $context, stdClass $user): bool {
        return rb_facetoface_summary_room_embedded::is_capable_static($user->id);
    }

    protected function get_report_header(seminar_attachment_item $item): string {
        return get_string('upcomingsessionsinroom', 'mod_facetoface');
    }

    protected function render_details(seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): string {
        /** @var room $item */
        return $renderer->render_room_details($item);
    }

    protected function render_empty(moodle_url $manageurl): string {
        return get_string('reports:selectroom', 'mod_facetoface', $manageurl->out());
    }

    /**
     * See if the user can manage ad-hoc rooms.
     *
     * @param user $user
     * @param context $context
     * @return boolean
     */
    private static function can_manage_custom_rooms(user $user, context $context): bool {
        return has_capability('mod/facetoface:manageadhocrooms', $context, $user->id);
    }

    /**
     * Serve a virtual meeting card when coming from the manage page.
     *
     * @return seminarresource_card|null always returns null; no card is served
     */
    private function virtual_meeting_card_data_from_manage(): ?seminarresource_card {
        return virtualroom_card_factory::none();
    }

    /**
     * Serve a virtual room card when coming from the manage page.
     *
     * @param room $room
     * @param user $user
     * @param context $context
     * @return seminarresource_card
     */
    private function virtual_room_card_data_from_manage(room $room, user $user, context $context): seminarresource_card {
        if (self::can_manage_custom_rooms($user, $context)) {
            return virtualroom_card_factory::go_to_room($room->get_name(), $room->get_url(), null);
        } else {
            return virtualroom_card_factory::unavailable();
        }
    }

    /**
     * Serve a virtual meeting card when coming from the event page.
     *
     * @param seminar_session $session
     * @param room $room
     * @param user $user
     * @param boolean $capable
     * @return seminarresource_card
     */
    private function virtual_meeting_card_data_from_event(seminar_session $session, room $room, user $user, bool $capable): seminarresource_card {
        $room_vm = room_dates_virtualmeeting::load_by_session_room($session, $room);
        if ($room_vm && $room_vm->get_status() !== room_dates_virtualmeeting::STATUS_AVAILABLE) {
            return $this->virtual_meeting_card_unavailable($session, $user, $room_vm, $capable);
        }
        $model = $room_vm->get_virtualmeeting();
        if ($model === null) {
            return virtualroom_card_factory::unavailable();
        }
        $room_url = $model->get_join_url(false);
        return $this->virtual_x_card_data_from_event($session, $room, $user, $capable, $room_url, $room_vm, $model);
    }

    /**
     * Serve a virtual room card when coming from the event page.
     *
     * @param seminar_session $session
     * @param room $room
     * @param user $user
     * @param boolean $capable
     * @return seminarresource_card
     */
    private function virtual_room_card_data_from_event(seminar_session $session, room $room, user $user, bool $capable): seminarresource_card {
        $room_url = $room->get_url();
        return $this->virtual_x_card_data_from_event($session, $room, $user, $capable, $room_url, null, null);
    }

    /**
     * Serve a virtual meeting/room card when coming from the event page.
     *
     * @param seminar_session $session
     * @param room $room
     * @param user $user
     * @param boolean $capable
     * @param string $room_url
     * @param room_dates_virtualmeeting|null $room_vm
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return seminarresource_card
     */
    private function virtual_x_card_data_from_event(seminar_session $session, room $room, user $user, bool $capable, string $room_url, ?room_dates_virtualmeeting $room_vm, ?virtual_meeting_model $model): seminarresource_card {
        if (empty($room_url)) {
            return virtualroom_card_factory::unavailable();
        }
        if ($capable) {
            return $this->virtual_x_card_data_from_event_for_superuser($session, $room, $user, $room_url, $model);
        } else {
            return $this->virtual_x_card_data_from_event_for_learner($session, $room, $user, $room_url, $model);
        }
    }

    /**
     * Serve a virtual meeting card with unavailable status when coming from the event page.
     *
     * @param seminar_session $session
     * @param user $user
     * @param room_dates_virtualmeeting $roomdate_vm
     * @param boolean $capable
     * @return seminarresource_card
     */
    private function virtual_meeting_card_unavailable(seminar_session $session, user $user, room_dates_virtualmeeting $roomdate_vm, bool $capable): seminarresource_card {
        if ($capable) {
            return $this->virtual_meeting_card_unavailable_for_superuser($roomdate_vm);
        } else {
            return $this->virtual_meeting_card_unavailable_for_learner($session, $user, $roomdate_vm);
        }
    }

    /**
     * Serve a virtual meeting card with unavailable status when coming from the event page.
     *
     * @param room_dates_virtualmeeting $roomdate_vm
     * @return seminarresource_card
     */
    private function virtual_meeting_card_unavailable_for_superuser(room_dates_virtualmeeting $roomdate_vm): seminarresource_card {
        $status = $roomdate_vm->get_status();
        $statuses_update = [null, room_dates_virtualmeeting::STATUS_PENDING_UPDATE];
        $statuses_deletion = [room_dates_virtualmeeting::STATUS_UNAVAILABLE, room_dates_virtualmeeting::STATUS_PENDING_DELETION, room_dates_virtualmeeting::STATUS_FAILURE_DELETION];
        $builder = virtualroom_card::builder('x');
        if (in_array($status, $statuses_deletion, true)) {
            $builder->heading(get_string('virtualroom_card_over', 'mod_facetoface'));
        } else if (in_array($status, $statuses_update, true)) {
            $builder->heading(get_string('virtualroom_card_preparing', 'mod_facetoface'));
            $builder->instruction(get_string('virtualroom_card_wait', 'mod_facetoface'));
        } else {
            $builder->heading(get_string('virtualroom_card_unavailable', 'mod_facetoface'));
        }
        return $builder->build();
    }

    /**
     * Serve a virtual meeting card with unavailable status when coming from the event page.
     *
     * @param seminar_session $session
     * @param user $user
     * @param room_dates_virtualmeeting $roomdate_vm
     * @return seminarresource_card
     */
    private function virtual_meeting_card_unavailable_for_learner(seminar_session $session, user $user, room_dates_virtualmeeting $roomdate_vm): seminarresource_card {
        $status = $roomdate_vm->get_status();
        $statuses_deletion = [room_dates_virtualmeeting::STATUS_UNAVAILABLE, room_dates_virtualmeeting::STATUS_PENDING_DELETION, room_dates_virtualmeeting::STATUS_FAILURE_DELETION];
        $signup = signup::create($user->id, $session->get_seminar_event());
        $builder = virtualroom_card::builder('x');
        if (signup_helper::is_booked($signup, false) && in_array($status, $statuses_deletion, true)) {
            $builder->heading(get_string('virtualroom_card_over', 'mod_facetoface'));
        } else {
            $builder->heading(get_string('virtualroom_card_unavailable', 'mod_facetoface'));
        }
        return $builder->build();
    }

    /**
     * Serve a virtual meeting/room card for managers, trainer and facilitators when coming from the event page.
     *
     * @param seminar_session $session
     * @param room $room
     * @param user $user
     * @param string $room_url
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return seminarresource_card
     */
    private function virtual_x_card_data_from_event_for_superuser(seminar_session $session, room $room, user $user, string $room_url, ?virtual_meeting_model $model): seminarresource_card {
        if ($model && $user->id == $model->userid) {
            $host_url = $model->get_host_url(false);
            if ($host_url) {
                return virtualroom_card_factory::host_or_join($room->get_name(), $room_url, $host_url, $model);
            }
        }
        return virtualroom_card_factory::go_to_room($room->get_name(), $room_url, $model);
    }

    /**
     * Serve a virtual meeting/room card for learners when coming from the event page.
     *
     * @param seminar_session $session
     * @param room $room
     * @param user $user
     * @param string $room_url
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return seminarresource_card
     */
    private function virtual_x_card_data_from_event_for_learner(seminar_session $session, room $room, user $user, string $room_url, ?virtual_meeting_model $model): seminarresource_card {
        $signup = signup::create($user->id, $session->get_seminar_event());
        if (signup_helper::is_booked($signup, false)) {
            if ($session->is_over() || $signup->get_seminar_event()->get_cancelledstatus()) {
                return virtualroom_card_factory::no_longer_available();
            }
            if (room_helper::has_time_come($session->get_seminar_event(), $session)) {
                return virtualroom_card_factory::join_now($room->get_name(), $room_url, $session, $model);
            }
            return virtualroom_card_factory::will_open($session);
        }
        return virtualroom_card_factory::unavailable();
    }

    protected function render_card(?seminar_session $session, seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): ?seminarresource_card {
        /** @var room $item */
        if (!$item->is_virtual()) {
            return null;
        }

        $user = new user($user, false);
        $context = $renderer->getcontext();
        if ($session !== null) {
            $capable = self::can_manage_custom_rooms($user, $context) || room_helper::has_access_at_any_time($session, $user->id);
            if ($item->is_virtual_meeting()) {
                return $this->virtual_meeting_card_data_from_event($session, $item, $user, $capable);
            } else {
                return $this->virtual_room_card_data_from_event($session, $item, $user, $capable);
            }
        } else {
            if ($item->is_virtual_meeting()) {
                return $this->virtual_meeting_card_data_from_manage();
            } else {
                return $this->virtual_room_card_data_from_manage($item, $user, $context);
            }
        }
    }

    protected function render_banner(?seminar_session $session, seminar_attachment_item $item, stdClass $user, mod_facetoface_renderer $renderer): ?notification {
        /** @var room $item */
        if ($session === null) {
            return null;
        }
        $room_vm = room_virtualmeeting::get_virtual_meeting($item);
        if (!$room_vm->exists() || $room_vm->get_userid() != $user->id) {
            return null;
        }
        $roomdate_vm = room_dates_virtualmeeting::load_by_session_room($session, $item);
        $failure_statuses = [room_dates_virtualmeeting::STATUS_FAILURE_CREATION, room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, room_dates_virtualmeeting::STATUS_FAILURE_DELETION];
        if (!in_array($roomdate_vm->get_status(), $failure_statuses, true)) {
            return null;
        }
        $url = new moodle_url('/mod/facetoface/events/edit.php', ['s' => $session->get_sessionid(), 'backtoallsessions' => 1]);
        $notification = new notification(get_string('virtualroom_banner_retry', 'mod_facetoface', $url->out()), notification::NOTIFY_WARNING);
        $notification->set_extra_classes(['mod_facetoface__resource-card__notification']);
        return $notification;
    }

    protected function get_manage_button(bool $frommanage): string {
        if ($frommanage) {
            return get_string('backtorooms', 'mod_facetoface');
        } else {
            return get_string('viewallrooms', 'mod_facetoface');
        }
    }

    protected function get_manage_url(bool $frommanage): moodle_url {
        return new moodle_url('/mod/facetoface/room/manage.php');
    }

    protected function get_edit_button(seminar_attachment_item $item): string {
        return get_string('editroom', 'mod_facetoface');
    }

    protected function get_edit_url(seminar_attachment_item $item): ?moodle_url {
        /** @var room $item */
        if ($item->get_custom()) {
            return null;
        } else {
            return new moodle_url('/mod/facetoface/room/edit.php', ['id' => $item->get_id()]);
        }
    }
}
