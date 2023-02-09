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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\notification;

use coding_exception;
use core\entity\user as user_entity;
use core\message\message;
use core_user;
use mod_perform\models\activity\external_participant as external_participant_model;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\models\activity\notification_recipient as notification_recipient_model;
use mod_perform\models\activity\participant as participant_model;
use stdClass;
use totara_core\relationship\relationship as relationship_model;
use totara_core\totara_user;

/**
 * The mailer class.
 * deprecated Since Totara 17.0
 */
class mailer {
    /** @var notification_recipient_model[] */
    private $recipients;

    /** @var composer */
    private $composer;

    /** @var integer */
    private $course_id;

    /**
     * Temporarily holds the language which was set before sending out the notification
     *
     * @var string
     */
    private $forced_lang;

    /**
     * Constructor. *Do not instantiate this class directly. Use the factory class.*
     *
     * @param notification_model $notification
     * @internal
     * @deprecated Since Totara 17.0
     */
    public function __construct(notification_model $notification) {
        debugging('Do not use mailer, perform now uses centralised notifications', DEBUG_DEVELOPER);
        $this->recipients = notification_recipient_model::load_by_notification($notification, true);
        $this->composer = factory::create_composer($notification->class_key);
        $this->course_id = $notification->activity->course;
    }

    /**
     * Return true if the notification has any recipients.
     *
     * @return boolean
     * @deprecated Since Totara 17.0
     */
    public function has_recipients(): bool {
        debugging('Do not use has_recipients any more, perform now uses centralised notifications', DEBUG_DEVELOPER);
        return count($this->recipients) > 0;
    }

    /**
     * Post a notification.
     *
     * @param user_entity|stdClass|participant_model|external_participant_model $user
     * @param relationship_model $relationship
     * @param placeholder $placeholders
     * @return boolean
     * @throws coding_exception parameter $user is invalid
     * @deprecated since Totara 17.0
     */
    public function post($user, relationship_model $relationship, placeholder $placeholders): bool {
        global $USER, $SESSION;
        debugging('Do not use post any more, perform now uses centralised notifications', DEBUG_DEVELOPER);

        $recipient = $this->resolve_recipient($relationship);
        if (!$recipient) {
            return false;
        }
        if (!$this->composer->set_relationship($relationship)) {
            return false;
        }
        if ($user instanceof participant_model) {
            // Set $user to either user_entity or external_participant_model.
            $user = $user->get_user();
        }
        if ($user instanceof external_participant_model) {
            // Convert $user from external_participant_model to the good-old stdClass containing a dummy user record.
            $user = totara_user::get_external_user($user->email);
        } else if ($user instanceof user_entity) {
            // Convert $user from user_entity to the good-old stdClass.
            $user = $user->to_record();
        }
        if (!($user instanceof stdClass)) {
            throw new coding_exception('invalid user passed');
        }

        $this->change_language($user);

        $is_reminder = $this->composer->is_reminder();
        $message = $this->composer->compose($placeholders);
        $this->send_notification(core_user::get_noreply_user(), $user, $message, $is_reminder);

        $this->reset_language();

        $this->save_history($recipient);

        return true;
    }

    /**
     * Make sure we are sending the notification in the preferred language of the recipient
     * or if it's an external participant in the default language of the site
     *
     * @param stdClass $user
     * @return void
     */
    private function change_language(stdClass $user): void {
        global $SESSION;

        $this->forced_lang = $SESSION->forcelang ?? null;

        force_current_language($user->lang);
    }

    /**
     * Reset force language if not forced previously otherwise force it back to what it was.
     *
     * @return void
     */
    private function reset_language(): void {
        force_current_language($this->forced_lang);
        $this->forced_lang = null;
    }

    /**
     * Save a historical record for testing.
     *
     * @param notification_recipient_model $recipient
     */
    private function save_history(notification_recipient_model $recipient): void {
        $sink = factory::create_sink();
        if ($sink) {
            $sink->push($recipient, $this->composer, time());
        }
    }

    /**
     * Send a notification.
     *
     * @param stdClass $from user object or user id or NOREPLY_USER or SUPPORT_USER
     * @param stdClass $to user object or user id or NOREPLY_USER or SUPPORT_USER
     * @param message $message
     * @param bool $is_reminder set true to send through the reminder channel instead of the notification channel
     */
    private function send_notification(stdClass $from, stdClass $to, message $message, bool $is_reminder): void {
        $eventdata = clone $message;
        $eventdata->courseid         = $this->course_id;
        $eventdata->modulename       = 'perform';
        $eventdata->userfrom         = $from;
        $eventdata->userto           = $to;

        $eventdata->name = $is_reminder ? 'activity_reminder' : 'activity_notification';
        $eventdata->component       = 'mod_perform';
        $eventdata->notification    = 1;

        message_send($eventdata);
    }

    /**
     * @param relationship_model $relationship
     * @return notification_recipient_model|null
     */
    private function resolve_recipient(relationship_model $relationship): ?notification_recipient_model {
        foreach ($this->recipients as $recipient) {
            if ($recipient->core_relationship_id == $relationship->id) {
                return $recipient;
            }
        }
        return null;
    }
}
