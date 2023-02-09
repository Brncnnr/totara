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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_engage\access\access;
use totara_engage\share\provider as share_provider;
use totara_engage\share\recipient\manager as recipient_manager;
use totara_engage\share\manager as share_manager;
use totara_engage\webapi\middleware\require_valid_recipients;

/**
 * Resolver for sharing multiple items with one recipient.
 */
class share_with_recipient extends mutation_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $DB, $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $transaction = $DB->start_delegated_transaction();

        $items = $args['items'];
        $recipients = recipient_manager::create_from_array([$args['recipient']]);

        // Get an instance for each item.
        foreach ($items as $item) {
            $provider = share_provider::create($item['component']);
            $instance = $provider->get_item_instance($item['itemid']);

            // If the user can update this instance make sure that it has at least the minimum
            // required recipient access.
            if ($instance->can_update($USER->id)) {
                $access = $instance->get_access();
                $recipient = reset($recipients);
                if (!access::is_public($access) && $access !== $recipient->get_minimum_access()) {
                    $provider->update_access($instance, $recipient->get_minimum_access(), $USER->id);
                }
            }

            // Share the item.
            share_manager::share($instance, $item['component'], $recipients);
        }

        $transaction->allow_commit();

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
            new require_valid_recipients('recipient'),
        ];
    }

}