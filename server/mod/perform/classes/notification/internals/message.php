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

namespace mod_perform\notification\internals;

use coding_exception;
use mod_perform\models\activity\notification_recipient as notification_recipient_model;

/**
 * A notification history record.
 *
 * @property-read integer $notification_id
 * @property-read integer $relationship_id
 * @property-read string $class_key
 * @property-read integer $sent_at
 * @codeCoverageIgnore
 */
final class message {
    /** @var integer */
    private $notification_id;

    /** @var integer */
    private $relationship_id;

    /** @var string */
    private $class_key;

    /** @var integer */
    private $sent_at;

    /**
     * Constructor.
     *
     * @param notification_recipient_model $recipient
     * @param string $class_key
     * @param integer $time
     */
    public function __construct(notification_recipient_model $recipient, string $class_key, int $time) {
        $this->notification_id = $recipient->notification_id;
        $this->relationship_id = $recipient->core_relationship_id;
        $this->class_key = $class_key;
        $this->sent_at = $time;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        if (method_exists($this, 'get_' . $name)) {
            $name = 'get_'. $name;
            return $this->{$name}();
        }
        throw new coding_exception('property does not exist: ' . $name);
    }

    /**
     * @return integer|null
     */
    public function get_notification_id(): ?int {
        return $this->notification_id ?: null;
    }

    /**
     * @return integer
     */
    public function get_relationship_id(): int {
        return $this->relationship_id;
    }

    /**
     * @return string
     */
    public function get_class_key(): string {
        return $this->class_key;
    }

    /**
     * @return integer
     */
    public function get_sent_at(): int {
        return $this->sent_at;
    }
}
