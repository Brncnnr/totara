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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin;

use core\base_plugin_config;

class config extends base_plugin_config {
    /**
     * @var int
     */
    private const MAX_SELECTED_ITEMS_NUMBER = 50;

    /**
     * @var string
     */
    public const ACCESS_TOKEN_ENDPOINT = 'https://www.linkedin.com/oauth/v2/accessToken';

    /**
     * This is Totara partner identifier within linkedin learning system.
     * For more information, see https://docs.microsoft.com/en-us/linkedin/learning/getting-started/partner-identifier
     *
     * @var string
     */
    public const PARTNER_IDENTIFIER = 'urn:li:partner:totara';

    /**
     * @return string
     */
    protected static function get_component(): string {
        return 'contentmarketplace_linkedin';
    }

    /**
     * @return string|null
     */
    public static function client_id(): ?string {
        return static::get('client_id');
    }

    /**
     * @return string|null
     */
    public static function client_secret(): ?string {
        return static::get('client_secret');
    }

    /**
     * Returns the value of access token.
     * @return string|null
     */
    public static function access_token(): ?string {
        return static::get('access_token');
    }

    /**
     * Returns the expiry dates of the access token.
     * @return int|null
     */
    public static function access_token_expiry(): ?int {
        $expiry = static::get('access_token_expiry');
        if (null === $expiry) {
            return null;
        }

        return (int) $expiry;
    }

    /**
     * @return string
     */
    public static function access_token_endpoint(): string {
        return static::get('access_token_endpoint', static::ACCESS_TOKEN_ENDPOINT);
    }

    /**
     * @param string|null $value
     */
    public static function save_access_token(?string $value): void {
        static::set('access_token', $value);
    }

    /**
     * @param int|null $value
     */
    public static function save_access_token_expiry(?int $value): void {
        static::set('access_token_expiry', $value);
    }

    /**
     * @return bool
     */
    public static function completed_initial_sync_learning_asset(): bool {
        return static::get('completed_initial_sync_learning_asset', false);
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function save_completed_initial_sync_learning_asset(bool $value): void {
        static::set('completed_initial_sync_learning_asset', $value);
    }

    /**
     * @return int|null
     */
    public static function last_time_sync_learning_asset(): ?int {
        return static::get('last_time_sync_learning_asset');
    }

    /**
     * @param int $value
     * @return void
     */
    public static function save_last_time_sync_learning_asset(int $value): void {
        static::set('last_time_sync_learning_asset', $value);
    }

    /**
     * @return int
     */
    public static function get_max_selected_items_number(): int {
        return static::get('max_selected_learning_items', static::MAX_SELECTED_ITEMS_NUMBER);
    }

    /**
     * Mainly used for testing to reduce or increase the threshold number
     * of learning objects that we can pipe it to the adhoc tasks.
     *
     * @param int $number
     * @return void
     */
    public static function set_max_selected_items_number(int $number): void {
        static::set('max_selected_learning_items', $number);
    }

    /**
     * @return bool
     */
    public static function completed_initial_sync_classification(): bool {
        return static::get('completed_initial_sync_classification', false);
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function save_completed_initial_sync_classification(bool $value): void {
        static::set('completed_initial_sync_classification', $value);
    }
}