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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\link\metadata_info;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_resolver_query_get_linkmetadata_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/tests/fixtures/link/http_mock_request.php");

        parent::setUpBeforeClass();
    }

    protected function setUp(): void {
        // Clear core_link mock url
        \http_mock_request::clear();
    }

    protected function tearDown(): void {
        // Clear core_link mock url
        \http_mock_request::clear();
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_error_ajax_query(): void {
        $this->setAdminUser();

        $result = $this->resolve_graphql_query('core_get_linkmetadata', ['url' => '']);
        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function test_query_get_linkmetadata(): void {
        global $CFG;

        $this->setAdminUser();

        // We need to use an existing IP for now as
        // internally the code would do a DNS lookup on a host
        $mock_url = 'https://8.8.8.8';

        http_mock_request::add_url(
            $mock_url,
            "{$CFG->dirroot}/lib/tests/fixtures/link/page/sample_page.html"
        );

        /** @var metadata_info $metadata_info */
        $metadata_info = $this->resolve_graphql_query('core_get_linkmetadata', ['url' => $mock_url]);

        $this->assertNotEmpty($metadata_info);
        $this->assertSame('Sample page', $metadata_info->get_title());
        $this->assertSame('Page sample', $metadata_info->get_description());

        $this->assertSame('https://example.com', $metadata_info->get_url()->out());
        $this->assertSame('https://example.com', $metadata_info->get_image()->out());

        $this->assertSame(111, $metadata_info->get_video_height());
        $this->assertSame(222, $metadata_info->get_video_width());
    }

    /**
     * @return void
     */
    public function test_query_get_invalid_link_metadata(): void {
        global $CFG;

        $this->setAdminUser();
        $mock_url = 'https://example.com';

        http_mock_request::add_url(
            $mock_url,
            "{$CFG->dirroot}/lib/tests/fixtures/link/page/malicious_sample_page.html"
        );

        /** @var metadata_info $metadata_info */
        $metadata_info = $this->resolve_graphql_query('core_get_linkmetadata', ['url' => $mock_url]);
        $this->assertNotEmpty($metadata_info);
        $this->assertNotNull($metadata_info);

        $this->assertSame("alert('hello world');", $metadata_info->get_title());
        $this->assertSame("This is description", $metadata_info->get_description());
        $this->assertNull($metadata_info->get_url());
        $this->assertNull($metadata_info->get_video_width());
        $this->assertNull($metadata_info->get_video_height());
    }

}