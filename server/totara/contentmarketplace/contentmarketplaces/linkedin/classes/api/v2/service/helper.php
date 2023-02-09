<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service;

use moodle_url;

class helper {
    /**
     * The function behaves pretty much as same as PHP native function {@see parse_str()}.
     * However, it also handles the parameters that are considered as invalid in PHP,
     * for example: `assetFilteringCriteria.locales[0].country=US`
     *
     * It does not use "parse_str" because "parse_str" will convert this parameter "assetFilteringCriteria.locales[0].country=US"
     * into something like "[assetFilteringCriteria_locales => [0 => 'US']]" which is not ideally.
     *
     * The class {@see moodle_url} had also been considered, however, underneath moodle_url, it also does parse
     * the query parameters pretty much as same as "parse_str".
     *
     * $paging_href value example: /v2/learningAssets?assetFilteringCriteria.locales[0].country=US
     * The functions will result parameters from $paging_href above into:
     * [
     *  'assetFilteringCriteria.locales' => [
     *      ['country' => 'US']
     *  ]
     * ]
     *
     * @param string $paging_href
     * @return array
     */
    public static function parse_query_parameters_from_url(string $paging_href): array {
        [$unused, $query_parameter] = explode('?', $paging_href);
        unset($unused);

        $parameters = explode('&', $query_parameter);
        $rtn_params = [];

        $index_regex = '/\[[0-9]+]$/i';
        $index_with_key_regex = '/\[([0-9]+)]\.([a-z]+)$/i';

        foreach ($parameters as $parameter) {
            /**
             * @var string $key
             * @var string $value
             */
            [$key, $value] = explode('=', $parameter);

            if (preg_match($index_regex, $key)) {
                $key = preg_replace($index_regex, '', $key);

                if (!isset($rtn_params[$key])) {
                    $rtn_params[$key] = [];
                }

                $rtn_params[$key][] = $value;
                continue;
            }

            $matches = [];
            if (preg_match($index_with_key_regex, $key, $matches)) {
                $group_key = preg_replace($index_with_key_regex, '', $key);
                if (!isset($rtn_params[$group_key])) {
                    $rtn_params[$group_key] = [];
                }

                $group_index = $matches[1];
                $group_key_attribute = $matches[2];

                if (!isset($rtn_params[$group_key][$group_index])) {
                    $rtn_params[$group_key][$group_index] = [];
                }

                $rtn_params[$group_key][$group_index][$group_key_attribute] = $value;
                continue;
            }

            if (is_numeric($value)) {
                // Cast to integer.
                $value = clean_param($value, PARAM_INT);
            } else if (self::looks_like_boolean($value)) {
                // Cast to boolean if the string looks like boolean.
                $value = (bool) clean_param($value, PARAM_BOOL);
            }

            $rtn_params[$key] = $value;
        }

        return $rtn_params;
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function looks_like_boolean(string $value): bool {
        return in_array($value, ['true', 'false']);
    }
}