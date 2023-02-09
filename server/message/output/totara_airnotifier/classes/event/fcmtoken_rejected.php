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
 * @author David Curry <david.curry@totaralearning.com>
 * @package message_totara_airnotifier
 */

namespace message_totara_airnotifier\event;

use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Class alert_sent
 *
 * @package totara_message
 */
class fcmtoken_rejected extends \core\event\base {

    /**
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_OTHER;
    }

    /**
     * Implements get_name().
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_fcmtoken_rejected', 'message_totara_airnotifier');
    }

    /**
     * Implements get_description().
     *
     * @return string
     */
    public function get_description() {
        return 'A fcmtoken was rejected by the airnotifier server';
    }

    /**
     * Create an event instance from a givent token
     *
     * @param \stdClass $eventdata
     * @return \message_totara_airnotifier\event\fcmtoken_rejected
     */
    public static function create_from_token(string $fcmtoken) {
        self::$preventcreatecall = false;

        $event = self::create(
            array(
                'context' => context_system::instance(),
                'other'   => [
                    'fcmtoken' => $fcmtoken
                ],
            )
        );

        self::$preventcreatecall = true;
        return $event;
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    public function validate_data() {

        parent::validate_data();

        if (self::$preventcreatecall) {
            throw new \coding_exception('Cannot call create() directly, use create_from_message_data() instead.');
        }

        if (empty($this->other['fcmtoken'])) {
            throw new \coding_exception('Cannot call create() with an empty token');
        }
    }
}
