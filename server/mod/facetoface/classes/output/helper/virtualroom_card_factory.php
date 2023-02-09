<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

namespace mod_facetoface\output\helper;

use mod_facetoface\output\builder\virtualroom_card_builder as card_builder;
use mod_facetoface\output\seminarevent_detail_section;
use mod_facetoface\output\virtualroom_card as card;
use mod_facetoface\output\session_time;
use mod_facetoface\seminar_session;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;

/**
 * Provides the preset of virtual room cards.
 */
final class virtualroom_card_factory {
    /**
     * No card.
     *
     * @return card|null
     */
    public static function none(): ?card {
        return null;
    }

    /**
     * Virtual room is unavailable.
     *
     * @return card
     */
    public static function unavailable(): card {
        return card::builder(get_string('virtualroom_card_unavailable', 'mod_facetoface'))->build();
    }

    /**
     * Virtual room is no longer available.
     *
     * @return card
     */
    public static function no_longer_available(): card {
        return card::builder(get_string('virtualroom_card_over', 'mod_facetoface'))->build();
    }

    /**
     * Virtual room will open 15 minutes before next session.
     *
     * @param seminar_session $session
     * @return card
     */
    public static function will_open(seminar_session $session): card {
        return card::builder(get_string('virtualroom_card_willopen', 'mod_facetoface'))
            ->active()
            ->details(self::summary($session))
            ->build();
    }

    /**
     * Go to room.
     *
     * @param string $room_name
     * @param string $join_url
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return card
     */
    public static function go_to_room(string $room_name, string $join_url, ?virtual_meeting_model $model): card {
        return self::builder_available($model)
            ->active()
            ->button(get_string('roomgoto', 'mod_facetoface'))
                ->primary()
                ->link($join_url)
                ->hint(get_string('roomgotox', 'mod_facetoface', $room_name))
                ->copyable(get_string('copyroomlink', 'mod_facetoface'))
                ->done()
            ->preview($model !== null ? $model->get_preview(false) : '')
            ->build();
    }

    /**
     * Host meeting or Join as attendee.
     *
     * @param string $room_name
     * @param string $join_url
     * @param string $host_url
     * @param virtual_meeting_model $model
     * @return card
     */
    public static function host_or_join(string $room_name, string $join_url, string $host_url, virtual_meeting_model $model): card {
        return self::builder_available($model)
            ->active()
            ->button(get_string('roomhost', 'mod_facetoface'))
                ->primary()
                ->link($host_url)
                ->hint(get_string('roomhostx', 'mod_facetoface', $room_name))
                ->done()
            ->button(get_string('roomhostjoin', 'mod_facetoface'))
                ->link($join_url)
                ->hint(get_string('roomhostjoinx', 'mod_facetoface', $room_name))
                ->copyable(get_string('copyroomlink', 'mod_facetoface'))
                ->done()
            ->preview($model !== null ? $model->get_preview(false) : '')
            ->build();
    }

    /**
     * Join now.
     *
     * @param string $room_name
     * @param string $join_url
     * @param seminar_session $session
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return card
     */
    public static function join_now(string $room_name, string $join_url, seminar_session $session, ?virtual_meeting_model $model): card {
        return self::builder_available($model)
            ->active()
            ->details(self::summary($session))
            ->button(get_string('roomjoinnow', 'mod_facetoface'))
                ->primary()
                ->link($join_url)
                ->hint(get_string('roomjoinnowx', 'mod_facetoface', $room_name))
                ->copyable(get_string('copyroomlink', 'mod_facetoface'))
                ->done()
            ->build();
    }

    /**
     * Get the builder of an available virtual room/meeting.
     *
     * @param virtual_meeting_model|null $model model instance for a virtual meeting, null for a virtual room
     * @return card_builder
     */
    private static function builder_available(?virtual_meeting_model $model): card_builder {
        if ($model !== null) {
            $heading = get_string('virtualroom_headingx', 'mod_facetoface', $model->get_plugin_name());
        } else {
            $heading = get_string('virtualroom_heading', 'mod_facetoface');
        }
        return card::builder($heading);
    }

    /**
     * Summarise the session.
     *
     * @param seminar_session $session
     * @return seminarevent_detail_section
     */
    private static function summary(seminar_session $session): seminarevent_detail_section {
        return seminarevent_detail_section::builder()
            ->show_divider(false)
            ->add_detail(get_string('virtualroom_details_seminar', 'mod_facetoface'), $session->get_seminar_event()->get_seminar()->get_name())
            ->add_detail_unsafe(get_string('virtualroom_details_session_time', 'mod_facetoface'), session_time::to_html($session->get_timestart(), $session->get_timefinish(), $session->get_sessiontimezone()))
            ->build();
    }
}
