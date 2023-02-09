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

use contentmarketplace_linkedin\api\v2\service\helper;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_service_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_parse_query_parameters_from_uri_case_1(): void {
        $uri = implode(
            '&',
            [
                '/v2/learningAssets?assetFilteringCriteria.assetTypes[0]=COURSE',
                'assetFilteringCriteria.licensedOnly=true',
                'assetFilteringCriteria.locales[0].country=US',
                'assetFilteringCriteria.locales[0].language=en',
                'assetRetrievalCriteria.expandDepth=1',
                'assetRetrievalCriteria.includeRetired=true',
                'count=2',
                'q=criteria',
                'start=3398'
            ]
        );

        $parameters = helper::parse_query_parameters_from_url($uri);
        ksort($parameters);

        $expected_parameters = [
            'assetFilteringCriteria.assetTypes' => ['COURSE'],
            'assetFilteringCriteria.licensedOnly' => true,
            'assetFilteringCriteria.locales' => [
                [
                    'country' => 'US',
                    'language' => 'en',
                ]
            ],
            'assetRetrievalCriteria.expandDepth' => 1,
            'assetRetrievalCriteria.includeRetired' => true,
            'count' => 2,
            'q' => 'criteria',
            'start' => 3398
        ];
        ksort($expected_parameters);

        self::assertSame($expected_parameters, $parameters);
    }
}