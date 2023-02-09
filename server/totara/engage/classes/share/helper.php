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

use theme_config;
use totara_engage\share\recipient\manager as recipient_manager;
use totara_engage\share\recipient\helper as recipient_helper;
use totara_engage\share\recipient\recipient;

final class helper {

    /**
     * Get grouped recipients into the required graphql structure.
     *
     * @param array $recipients
     * @return array
     */
    public static function group_recipient_totals(array $recipientcounts): array {
        $totals = [
            'totalrecipients' => 0,
            'recipients' => []
        ];

        // Get the grouped recipient totals into the correct structure.
        foreach ($recipientcounts as $recipientcount) {
            $info = recipient_helper::get_recipient_area_info($recipientcount->area);
            $totals['totalrecipients'] += $recipientcount->total;
            $totals['recipients'][] = [
                'area' => $recipientcount->area,
                'label' => $info['label'],
                'total' => (int)$recipientcount->total
            ];
        }

        return $totals;
    }

    /**
     * Get recipients into the required graphql structure.
     *
     * @since Totara 13.6 added parameter $theme_config
     *
     * @param array $recipients
     * @param theme_config|null $theme_config
     * @return array
     */
    public static function format_recipients(array $recipients, ?theme_config $theme_config = null): array {
        $recipients = recipient_manager::create_from_array($recipients);

        // Get data for each recipient.
        $ret = [];

        /** @var recipient $recipient */
        foreach ($recipients as $recipient) {
            // Only user is explicitly specified as a return type in the graphql query
            // as we need the user to pass through the graphql user type resolver's
            // validations.
            $area = strtolower($recipient->get_area());
            if ($area !== 'user') {
                $area = 'other';
            }

            $ret[] = [
                'component' => $recipient->get_component(),
                'area' => $recipient->get_area(),
                'instanceid' => $recipient->get_id(),
                $area => $recipient->get_data($theme_config),
            ];
        }

        return $ret;
    }

    /**
     * @param string $component
     * @return string
     */
    public static function get_provider_type(string $component): string {
        $provider = provider::create($component);
        return $provider->get_provider_type();
    }

    /**
     * @param string $component
     * @param int $item_id
     * @return string|null

     */
    public static function get_resource_name(string $component, int $item_id): ?string {
        $provider = provider::create($component);
        $item = $provider->get_item_instance($item_id);

        return $item->get_name();
    }
}