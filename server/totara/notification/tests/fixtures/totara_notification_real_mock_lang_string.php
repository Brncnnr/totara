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
 * @package totara_notification
 */

/**
 * A tweak lang string that does not work relying on the cache system.
 */
class totara_notification_real_mock_lang_string extends lang_string {
    /**
     * totara_notification_real_mock_lang_string constructor.
     * @param string     $identifier
     * @param string     $component
     * @param array|null $a
     */
    public function __construct(string $identifier, string $component, ?array $a = null) {
        parent::__construct($identifier, $component, $a);
    }

    /**
     * @return string
     */
    public function get_string(): string {
        $language = current_language();
        $manager = get_string_manager();

        return $manager->get_string(
            $this->identifier,
            $this->component,
            $this->a,
            $language
        );
    }
}