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

use DateTime;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\account\conversation_account;
use totara_msteams\botfw\account\converter\channel_account_converter;
use totara_msteams\botfw\account\converter\channel_accounts_converter;
use totara_msteams\botfw\account\converter\conversation_account_converter;
use totara_msteams\botfw\persist_object;

/**
 * @property-read string $type
 * @property-read string $id
 * @property-read DateTime $timestamp
 * @property-read string $serviceUrl
 * @property-read string $channelId
 * @property-read channel_account $from
 * @property-read conversation_account $conversation
 * @property-read channel_account $recipient
 * @property-read string $text
 * @property-read object $value
 * @property-read channel_account[] $membersAdded
 */
class activity extends persist_object {
    /**
     * @var array
     */
    private static $mappers = [
        'type' => 'string',
        'id' => 'string',
        'timestamp' => 'time',
        'localTimestamp' => 'time',
        'serviceUrl' => 'url',
        'channelId' => 'string',
        'from' => channel_account_converter::class,
        'conversation' => conversation_account_converter::class,
        'recipient' => channel_account_converter::class,
        'text' => 'string',
        'value' => 'object',
        'membersAdded' => channel_accounts_converter::class,
    ];

    /**
     * @inheritDoc
     */
    protected static function get_mapper(string $name): string {
        return self::$mappers[$name] ?? 'object';
    }
}
