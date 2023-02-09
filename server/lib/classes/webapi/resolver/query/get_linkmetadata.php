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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\webapi\resolver\query;

use coding_exception;
use core\link\reader_factory;
use core\webapi\execution_context;
use core\webapi\middleware\rate_limiter;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;

class get_linkmetadata extends query_resolver {

    /**
     * Limited to 20 request per minute
     */
    public const RATE_LIMIT = 20;
    public const LIMIT_TIME = 60;

    /**
     * @param array $args
     * @param execution_context $ec
     * @return array|mixed
     */
    public static function resolve(array $args, execution_context $ec) {
        $url = $args['url'];
        if (empty($url)) {
            return null;
        }

        try {
            /** @var \core\link\reader $reader */
            $reader = reader_factory::get_reader_classname($url);
            return $reader::get_metadata_info($url);
        } catch (coding_exception $e) {
            // Only catch the coding_exception for now, as there might be some things that failed because of dev env.
            debugging($e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new rate_limiter(self::RATE_LIMIT, self::LIMIT_TIME, false)
        ];
    }
}