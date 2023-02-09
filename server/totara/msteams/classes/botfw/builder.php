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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw;

use totara_msteams\botfw\builder\bot_builder;
use totara_msteams\botfw\builder\message_builder;
use totara_msteams\botfw\builder\messaging_extension_builder;
use totara_msteams\botfw\card\builder\action_builder;
use totara_msteams\botfw\card\builder\hero_card_builder;
use totara_msteams\botfw\card\builder\list_card_builder;
use totara_msteams\botfw\card\builder\list_item_builder;
use totara_msteams\botfw\card\builder\signin_card_builder;
use totara_msteams\botfw\card\builder\thumbnail_card_builder;

/**
 * A factory class for builder classes.
 */
abstract class builder {
    /**
     * @return bot_builder
     */
    public static function bot(): bot_builder {
        return new bot_builder();
    }

    /**
     * @return message_builder
     */
    public static function message(): message_builder {
        return new message_builder();
    }

    /**
     * @return messaging_extension_builder
     */
    public static function messaging_extension(): messaging_extension_builder {
        return new messaging_extension_builder();
    }

    /**
     * @return action_builder
     */
    public static function action(): action_builder {
        return new action_builder();
    }

    /**
     * @return hero_card_builder
     */
    public static function hero_card(): hero_card_builder {
        return new hero_card_builder();
    }

    /**
     * @return list_card_builder
     */
    public static function list_card(): list_card_builder {
        return new list_card_builder();
    }

    /**
     * @return list_item_builder
     */
    public static function list_item(): list_item_builder {
        return new list_item_builder();
    }

    /**
     * @return signin_card_builder
     */
    public static function signin_card(): signin_card_builder {
        return new signin_card_builder();
    }

    /**
     * @return thumbnail_card_builder
     */
    public static function thumbnail_card(): thumbnail_card_builder {
        return new thumbnail_card_builder();
    }
}
