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

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\api\v2\service\learning_asset\query\criteria;
use contentmarketplace_linkedin\dto\locale;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_query_criteria_testcase extends testcase {
    /**
     * @return void
     */
    public function test_set_parameters_from_uri(): void {
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
                'start=3398',
            ]
        );

        $criteria = new criteria();
        $criteria->set_parameters_from_paging_url($uri);

        $moodle_url = new moodle_url("/totara/contentmarketplace/marketplaces.php");
        self::assertEmpty($moodle_url->params());

        $criteria->apply_to_url($moodle_url);
        self::assertNotEmpty($moodle_url->params());

        $applied_parameters = $moodle_url->params();
        ksort($applied_parameters);

        $expected_parameters = [
            'assetFilteringCriteria.assetTypes[0]' => constants::ASSET_TYPE_COURSE,
            'assetFilteringCriteria.licensedOnly' => 'true',
            'assetFilteringCriteria.locales[0].country' => 'US',
            'assetFilteringCriteria.locales[0].language' => 'en',
            'assetRetrievalCriteria.expandDepth' => '1',
            'assetRetrievalCriteria.includeRetired' => 'true',
            'count' => '2',
            'q' => 'criteria',
            'start' => '3398',
        ];
        ksort($expected_parameters);

        // Moodle URL parameters get cast to string eventually.
        self::assertSame($expected_parameters, $applied_parameters);
    }

    /**
     * @return void
     */
    public function test_set_invalid_asset_types(): void {
        $criteria = new criteria();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid asset type: data');

        $criteria->set_asset_types(['data', constants::ASSET_TYPE_COURSE]);
    }

    /**
     * @return void
     */
    public function test_set_invalid_sort_by(): void {
        $criteria = new criteria();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid sort by: sort_by');

        $criteria->set_sort_by('sort_by');
    }

    /**
     * @return void
     */
    public function test_set_invalid_difficulty_level(): void {
        $criteria = new criteria();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid difficulty level: level_one');

        $criteria->set_difficulty_levels(['level_one']);
    }

    /**
     * @return void
     */
    public function test_apply_to_url_without_filter(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        $criteria->apply_to_url($moodle_url);
        self::assertEquals('http://example.com?q=criteria', $moodle_url->out());
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_asset_types(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        // Add asset types
        $criteria->set_asset_types([constants::ASSET_TYPE_COURSE]);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            implode(
                '&',
                [
                    'http://example.com?q=criteria',
                    'assetFilteringCriteria.assetTypes%5B0%5D=COURSE',
                ]
            ),
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_sort_by(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        // Add sort by
        $criteria->set_sort_by(constants::SORT_BY_RELEVANCE);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            'http://example.com?q=criteria&assetPresentationCriteria.sortBy=RELEVANCE',
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_licensed_only(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        $criteria->set_licensed_only(true);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            'http://example.com?q=criteria&assetFilteringCriteria.licensedOnly=true',
            $moodle_url->out(false)
        );

        $moodle_url->remove_all_params();
        $criteria->clear();

        $criteria->set_licensed_only(false);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            'http://example.com?q=criteria&assetFilteringCriteria.licensedOnly=false',
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_start_and_count(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        $criteria->set_start(15);
        $criteria->set_count(42);

        $criteria->apply_to_url($moodle_url);
        self::assertEquals(
            'http://example.com?q=criteria&start=15&count=42',
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_locales(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url('http://example.com');

        $criteria->set_locales([
            new locale('en', 'US'),
            new locale('ja', 'JP'),
        ]);

        $criteria->apply_to_url($moodle_url);
        self::assertEquals(
            implode(
                '&',
                [
                    'http://example.com?q=criteria',
                    'assetFilteringCriteria.locales%5B0%5D.language=en',
                    'assetFilteringCriteria.locales%5B0%5D.country=US',
                    'assetFilteringCriteria.locales%5B1%5D.language=ja',
                    'assetFilteringCriteria.locales%5B1%5D.country=JP',
                ]
            ),
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_last_modified_after(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url("http://example.com");

        $criteria->set_last_modified_after(1000);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            "http://example.com?q=criteria&assetFilteringCriteria.lastModifiedAfter=1000",
            $moodle_url->out(false),
        );
    }

    /**
     * @return void
     */
    public function test_apply_to_url_with_keyword(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url("http://example.com");

        $criteria->set_keyword("help");
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            "http://example.com?q=criteria&assetFilteringCriteria.keyword=help",
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_url_with_include_retired(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url("http://example.com");

        $criteria->set_include_retired(true);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            "http://example.com?q=criteria&assetRetrievalCriteria.includeRetired=true",
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_url_with_expand_depth(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url("http://example.com");

        $criteria->set_expand_depth(2);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            "http://example.com?q=criteria&assetRetrievalCriteria.expandDepth=2",
            $moodle_url->out(false)
        );
    }

    /**
     * @return void
     */
    public function test_apply_url_with_classifications(): void {
        $criteria = new criteria();
        $moodle_url = new moodle_url("http://example.com");

        $criteria->set_classifications(["urn:li:category:251"]);
        $criteria->apply_to_url($moodle_url);

        self::assertEquals(
            sprintf(
                "http://example.com?q=criteria&assetFilteringCriteria.classifications%s=%s",
                urlencode("[0]"),
                urlencode("urn:li:category:251")
            ),
            $moodle_url->out(false)
        );
    }
}