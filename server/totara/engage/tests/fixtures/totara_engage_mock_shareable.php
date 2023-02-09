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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
use totara_engage\share\shareable;
use totara_engage\share\shareable_result;
use totara_engage\share\share;

class totara_engage_mock_shareable implements shareable {
    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var bool
     */
    private $can_share;

    /**
     * @var shareable_result
     */
    private $shareable_result;

    /**
     * @var bool
     */
    private $can_reshare;

    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var bool
     */
    private $can_unshare;

    /**
     * @var string
     */
    private static $resource_type;

    /**
     * totara_engage_mock_shareable constructor.
     * @param int       $instance_id
     * @param int       $context_id
     * @param int       $user_id
     */
    public function __construct(int $instance_id, int $context_id, int $user_id) {
        $this->instance_id = $instance_id;
        $this->user_id = $user_id;
        $this->context_id = $context_id;
        $this->can_share = true;
        $this->can_reshare = true;
        $this->can_unshare = true;
        $this->shareable_result = new shareable_result();
    }

    /**
     * totara_engage_mock_shareable destruct
     */
    public function __destruct() {
        if (isset(static::$resource_type)) {
            static::$resource_type = null;
        }

        // Let all other properties handled by PHP.
        // We just need the static variables to be unset.
    }

    /**
     * @return context
     */
    public function get_context(): context {
        return context::instance_by_id($this->context_id);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_share(int $userid): bool {
        return $this->can_share;
    }

    /**
     * @param bool $can_share
     * @return void
     */
    public function set_can_share(bool $can_share): void {
        $this->can_share = $can_share;
    }

    /**
     * @return shareable_result
     */
    public function get_shareable(): shareable_result {
        return $this->shareable_result;
    }

    /**
     * @param shareable_result $shareable_result
     * @return void
     */
    public function set_shareable_result(shareable_result $shareable_result): void {
        $this->shareable_result = $shareable_result;
    }

    /**
     * @param share $share
     * @return void
     */
    public function shared(share $share): void {
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_reshare(int $userid): bool {
        return $this->can_reshare;
    }

    /**
     * @param bool $can_reshare
     * @return void
     */
    public function set_can_reshare(bool $can_reshare): void {
        $this->can_reshare = $can_reshare;
    }

    /**
     * @param int $userid
     * @return void
     */
    public function reshare(int $userid): void {
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->instance_id;
    }

    /**
     * @return string
     */
    public static function get_resource_type(): string {
        if (!isset(static::$resource_type)) {
            return 'totara_engage';
        }

        return static::$resource_type;
    }

    /**
     * @param string $resource_type
     * @return void
     */
    public static function set_resource_type(string $resource_type): void {
        static::$resource_type = $resource_type;
    }

    /**
     * @param int $sharer_id
     * @param bool|null $is_container
     * @return bool
     */
    public function can_unshare(int $sharer_id, ?bool $is_container = false): bool {
        return $this->can_unshare;
    }

    /**
     * @param bool $can_unshare
     * @return void
     */
    public function set_can_unshare(bool $can_unshare): void {
        $this->can_unshare = $can_unshare;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->user_id;
    }
}