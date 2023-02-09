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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\share;

use totara_engage\entity\share as share_entity;
use totara_engage\entity\share_recipient as recipient_entity;

final class share {

    /**
     * This share is hidden.
     */
    public const VISIBILITY_HIDDEN = 0;

    /**
     * This share is visible.
     */
    public const VISIBILITY_VISIBLE = 1;

    /**
     * Notification not sent to recipient.
     */
    public const NOT_NOTIFIED = 0;

    /**
     * Notification sent to user.
     */
    public const NOTIFIED = 1;

    /**
     * @var share_entity
     */
    private $share_entity;

    /**
     * @var recipient_entity
     */
    private $recipient_entity;

    /**
     * share constructor.
     * @param share_entity $share
     * @param recipient_entity $recipient
     */
    public function __construct(share_entity $share, recipient_entity $recipient) {
        $this->share_entity = $share;
        $this->recipient_entity = $recipient;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->share_entity->id;
    }

    /**
     * @return int
     */
    public function get_recipient_id(): int {
        return $this->recipient_entity->id;
    }

    /**
     * @return int
     */
    public function get_item_id(): int {
        return $this->share_entity->itemid;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->share_entity->component;
    }

    /**
     * @return int
     */
    public function get_sharer_id(): int {
        return $this->recipient_entity->sharerid;
    }

    /**
     * @return int
     */
    public function get_recipient_instanceid(): int {
        return $this->recipient_entity->instanceid;
    }

    /**
     * @return string
     */
    public function get_recipient_area(): string {
        return $this->recipient_entity->area;
    }

    /**
     * @return string
     */
    public function get_recipient_component(): string {
        return $this->recipient_entity->component;
    }

    /**
     * @return bool
     */
    public function is_notified(): bool {
        return $this->recipient_entity->notified === self::NOTIFIED;
    }

}