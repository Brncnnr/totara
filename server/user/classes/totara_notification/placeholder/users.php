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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_notification
 */
namespace core_user\totara_notification\placeholder;

use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

/**
 * Class users
 *
 * Represents a collection of users. For a placeholder it will output a comma-separated list of values, e.g. a list
 * of fullnames. This may be refactored to implement totara_notification\placeholder\abstraction\collection_placeholder
 * when suitable.
 *
 * @package core_user
 */
class users extends single_emptiable_placeholder {
    /**
     * @var user[]
     */
    private $user_placeholders;

    /**
     * @param array $user_placeholders
     */
    public function __construct(array $user_placeholders) {
        $this->user_placeholders = $user_placeholders;
    }

    /**
     * @param array $user_ids
     * @return users
     */
    public static function from_ids(array $user_ids): users {
        return new static(array_map(static function ($user_id) {
            return user::from_id($user_id);
        }, $user_ids));
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return user::get_options();
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return count($this->user_placeholders) > 0;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        $values = [];
        foreach ($this->user_placeholders as $user_placeholder) {
            $values[] = $user_placeholder->do_get($key);
        }
        return implode(', ', $values);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ($key === 'full_name_link') {
            return true;
        }

        return parent::is_safe_html($key);
    }
}