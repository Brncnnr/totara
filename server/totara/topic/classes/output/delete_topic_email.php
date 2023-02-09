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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\output;

use core\output\template;
use totara_topic\usage\item;

/**
 * This output component should only be used for rendering an email in format HTML only.
 * Caution: This MUST not be used else where - especially for web interface.
 */
final class delete_topic_email extends template {
    /**
     * @param string  $topicvalue
     * @param item[]  $items
     *
     * @return delete_topic_email
     */
    public static function create(string $topicvalue, array $items): delete_topic_email {
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception("This template item is only meaning for the building the email template");
        }

        $data = [
            'message' => get_string('topicdeletedmessage', 'totara_topic', $topicvalue),
            'usages' => []
        ];

        $urls = [];

        /** @var item $item */
        foreach ($items as $item) {
            $component = $item->get_component();

            if (!isset($urls[$component])) {
                $urls[$component] = [];
            }

            $urls[$component][] = [
                'name' => $item->get_label(),
                'url' => $item->get_url()
            ];
        }

        // Building the tree structure for outputing the email.
        foreach ($urls as $component => $o) {
            $data['usages'][] = [
                'label' => get_string('pluginname', $component),
                'has_url' => !empty($o),
                'urls' => $o
            ];
        }

        return new static($data);
    }
}