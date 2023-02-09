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

use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\account\conversation_account;
use totara_msteams\botfw\card\attachment;
use totara_msteams\botfw\card\converter\attachments_converter;

/**
 * @property-read string $type
 * @property-read channel_account $from
 * @property-read conversation_account $conversation
 * @property-read channel_account $recipient
 * @property-read string $text
 * @property-read string $summary
 * @property-read object $value
 * @property string $attachmentLayout
 * @property attachment[] $attachments
 */
class message extends activity {
    /**
     * @var array
     */
    private static $mappers = [
        'attachments' => attachments_converter::class,
    ];

    /**
     * @inheritDoc
     */
    protected static function get_mapper(string $name): string {
        return self::$mappers[$name] ?? parent::get_mapper($name);
    }
}
