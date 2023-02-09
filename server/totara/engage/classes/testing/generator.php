<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

namespace totara_engage\testing;

use core_user\totara_engage\share\recipient\user as user_recipient;
use totara_engage\share\shareable;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;

final class generator extends \core\testing\component_generator {

    /**
     * @param int $count
     * @return array
     */
    public function create_users(int $count): array {
        $users = [];
        for ($x = 1; $x <= $count; ++$x) {
            $user['firstname'] = "Some{$x}";
            $user['lastname'] = "Any{$x}";
            $users[] = $this->datagenerator->create_user($user);
        }

        return $users;
    }

    /**
     * @param array $users
     * @return array
     */
    public function create_user_recipients(array $users): array {
        $recipients = [];
        foreach ($users as $user) {
            $recipients[] = new user_recipient($user->id);
        }
        return $recipients;
    }

    /**
     * @param shareable $item
     * @param int $sharerid
     * @param array $recipients
     * @param int|null $owninguserid
     * @return share_model[]
     */
    public function share_item(shareable $item, int $sharerid, array $recipients, $owninguserid = null): array {
        $context = $item->get_context();

        // Make the create method public so we can test it.
        $class = new \ReflectionClass(share_manager::class);
        $method = $class->getMethod('create');
        $method->setAccessible(true);

        if ($owninguserid === null) {
            // The item knows ;)
            $owninguserid = $item->get_userid();
        }

        return $method->invokeArgs(null, [
            $item->get_id(),
            $owninguserid,
            $item::get_resource_type(),
            $recipients,
            $context->id,
            $sharerid
        ]);
    }

}
