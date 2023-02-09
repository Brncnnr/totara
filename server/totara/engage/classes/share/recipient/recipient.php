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

namespace totara_engage\share\recipient;

use theme_config;
use totara_engage\share\recipient\helper as recipient_helper;
use totara_engage\share\shareable;

abstract class recipient {

    /**
     * Area identifying this recipient.
     */
    public const AREA = '';

    /**
     * Recipient ID.
     *
     * @var int
     */
    protected $instanceid;

    /**
     * recipient constructor.
     *
     * @param int $instanceid
     */
    public function __construct(int $instanceid = 0) {
        $this->instanceid = $instanceid;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->instanceid;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return static::AREA;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return recipient_helper::get_component(static::class);
    }

    /**
     * Validate the self recipient. For example, it is to check whether the recipient
     * as user is still existing in the system or not.
     *
     * Note that this is to not checking whether
     */
    abstract public function validate(): void;

    /**
     * Checking whether the target recipient is able to see the share-able item or not.
     *
     * @param shareable $item
     * @return void
     */
    public function validate_against_share_item(shareable $item): void {
    }

    /**
     * Checking whether the actor is able to share the content to target recipient or not.
     *
     * @param int $actor_id
     * @return void
     */
    public function validate_against_actor(int $actor_id): void {
    }

    /**
     * Indicate that this recipient can unshare the resources
     * shared to it.
     *
     * @return bool
     */
    public function can_unshare_resources(): bool {
        return false;
    }

    /**
     * User space label for this recipient.
     *
     * @return string
     */
    abstract public function get_label(): string;

    /**
     * Summary describing this recipient.
     *
     * @return string
     */
    abstract public function get_summary(): string;

    /**
     * Get data for specific recipient.
     *
     * @since Totara 13.6 added parameter $theme_config
     *
     * @param theme_config|null $theme_config
     * @return mixed
     */
    abstract public function get_data(?theme_config $theme_config = null);

    /**
     * Get the minimum access required by an item to be shared with this recipient.
     *
     * @return int
     */
    abstract public function get_minimum_access(): int;

    /**
     * Search for recipient.
     *
     * @param string $search
     * @param shareable|null $instance
     * @return array|recipient[]
     */
    abstract public static function search(string $search, ?shareable $instance): array;

    /**
     * Confirms if the user is a valid recipient of this share or part of a group that permits
     * this user access to the shared item.
     *
     * @param shareable $instance
     * @param int $user_id
     * @return bool
     */
    abstract public static function is_user_permitted(shareable $instance, int $user_id): bool;
}