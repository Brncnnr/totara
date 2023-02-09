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
 * @package totara_engage
 */
namespace totara_engage\webapi\resolver\type;

use core\theme\helper as theme_helper;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_engage\access\access;
use totara_engage\card\card as abstract_card;
use totara_engage\formatter\card_formatter;

class card extends type_resolver {
    /**
     * @param string            $field
     * @param abstract_card     $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $USER, $CFG;

        if (!($source instanceof abstract_card)) {
            throw new \coding_exception("Invalid type of card being passed");
        }

        switch ($field) {
            case 'user':
                return $source->get_user();

            case 'access':
                $access = $source->get_access();
                return access::get_code($access);

            case 'extra':
                $theme_config = theme_helper::load_theme_config($args['theme'] ?? null);
                $extra = $source->get_extra_data($theme_config);
                $json = json_encode($extra);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new \coding_exception("Invalid json parsing: " . json_last_error_msg());
                }

                return $json;

            case 'comments':
                return $source->get_total_comments();

            case 'reactions':
                return $source->get_total_reactions();

            case 'sharedbycount':
                return $source->get_sharedbycount();

            case 'bookmarked':
                return $source->is_bookmarked($USER->id);

            case 'owned':
                $userid = $source->get_userid();
                return $userid == $USER->id;

            case 'topics':
                return $source->get_topics();

            case 'footnotes':
                return $source->get_footnotes($args);

            case 'url':
                return $source->get_url($args);

            case 'image':
                $theme_config = theme_helper::load_theme_config($args['theme'] ?? null);
                $card_image = $source->get_card_image($args['preview_mode'] ?? null, $theme_config);
                return $card_image ? $card_image->out(false) : null;

            case 'interactor':
                return $source->get_interactor();

            default:
                $format = null;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $formatter = new card_formatter($source);
                return $formatter->format($field, $format);
        }
    }
}