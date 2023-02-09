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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_message
 */
namespace totara_message\entity;

use coding_exception;
use core\orm\entity\entity;
use stdClass;
use totara_message\repository\message_metadata_repository;

/**
 * Entity class for table "ttr_message_metadata"
 *
 * @property int         $id
 * @property int|null    $messageid
 * @property int         $msgtype
 * @property int         $msgstatus
 * @property int         $processorid
 * @property int         $urgency
 * @property string|null $icon
 * @property string|null $onaccept
 * @property string|null $onreject
 * @property string|null $oninfo
 * @property int|null    $messagereadid
 * @property int|null    $notificationid
 * @property int|null    $timeread
 *
 * @method static message_metadata_repository repository()
 */
class message_metadata extends entity {
    /**
     * @var string
     */
    public const TABLE = 'message_metadata';

    /**
     * Magic function will be called when {@see message_metadata::set_attribute()} is called.
     *
     * @param int $message_type
     * @return void
     */
    protected function set_msgtype_attribute(int $message_type): void {
        global $CFG, $TOTARA_MESSAGE_TYPES;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        // Perform validation before set.
        if (!isset($TOTARA_MESSAGE_TYPES[$message_type])) {
            throw new coding_exception("Invalid message type value '{$message_type}'");
        }

        $this->set_attribute_raw('msgtype', $message_type);
    }

    /**
     * Magic function will be called when {@see message_metadata::set_attribute()} is called.
     *
     * @param int $message_status
     * @return void
     */
    protected function set_msgstatus_attribute(int $message_status): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $valid_statuses = [
            TOTARA_MSG_STATUS_UNDECIDED,
            TOTARA_MSG_STATUS_OK,
            TOTARA_MSG_STATUS_NOTOK,
        ];

        if (!in_array($message_status, $valid_statuses)) {
            throw new coding_exception("Invalid message status value '{$message_status}'");
        }

        $this->set_attribute_raw('msgstatus', $message_status);
    }

    /**
     * Magic function will be called when {@see message_metadata::set_attribute()} is called.
     *
     * @param int $urgency_value
     * @return void
     */
    protected function set_urgency_attribute(int $urgency_value): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $valid_values = [
            TOTARA_MSG_URGENCY_LOW,
            TOTARA_MSG_URGENCY_NORMAL,
            TOTARA_MSG_URGENCY_URGENT,
        ];

        if (!in_array($urgency_value, $valid_values)) {
            throw new coding_exception("Invalid urgency value '{$urgency_value}'");
        }

        $this->set_attribute_raw('urgency', $urgency_value);
    }

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return message_metadata_repository::class;
    }

    /**
     * @return stdClass
     */
    public function get_record(): stdClass {
        return (object) $this->get_attributes_raw();
    }
}