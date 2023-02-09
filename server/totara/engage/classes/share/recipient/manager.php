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

use totara_engage\entity\share_recipient;

final class manager {

    /**
     * factory constructor.
     */
    private function __construct() {
        // This class cannot be instantiated.
    }

    /**
     * Create a new recipient instance.
     *
     * @param int $id
     * @param string $component
     * @param string $area
     * @return recipient
     */
    public static function create(int $id, string $component, string $area): recipient {
        $class = helper::get_recipient_class($component, $area);

        return new $class($id);
    }

    /**
     * Create recipient instances for multiple recipients.
     *
     * @param array $recipients
     * @return recipient[]
     */
    public static function create_from_array(array $recipients): array {
        $instances = [];
        foreach ($recipients as $recipient) {
            $instances[] = self::create(
                $recipient['instanceid'],
                $recipient['component'],
                $recipient['area']
            );
        }

        return $instances;
    }

    /**
     * Create recipient instances for multiple recipient entities.
     *
     * @param share_recipient[] $recipients
     * @return recipient[]
     */
    public static function create_from_entity(array $recipients): array {
        $instances = [];
        foreach ($recipients as $recipient) {
            $instances[] = self::create(
                $recipient->instanceid,
                $recipient->component,
                $recipient->area
            );
        }

        return $instances;
    }

}