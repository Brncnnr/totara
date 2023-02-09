<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_webapi
 */

use core_phpunit\testcase;
use GraphQL\Utils\BuildSchema;
use totara_webapi\endpoint_type\factory;
use totara_webapi\graphql;
use totara_webapi\tool\schema_diff;

/**
 * Compares the current dev schema to the previously-released dev schema.
 */
class totara_webapi_breaking_changes_testcase extends testcase {

    private $old_schema;
    private $new_schema;

    /**
     * Sets up old and new schema to test for breaking changes.
     *
     * It is possible to use a local schema file for this test, place it at
     * server/totara/webapi/tests/fixtures/schema_diff/local_release.graphqls
     *
     * If local_release.graphqls only contains the word skip, these tests will be skipped.
     */
    protected function setUp(): void {
        global $CFG;

        // Allow technical partners to use a different released schema for comparison, or skip this test.
        if (!is_readable("{$CFG->dirroot}/totara/webapi/tests/fixtures/schema_diff/local_release.graphqls")) {
            // Previously-released schema should only be modified by a release manager.
            $old_path = "{$CFG->dirroot}/totara/webapi/tests/fixtures/schema_diff/previous_release.graphqls";
        } else {
            $old_path = "{$CFG->dirroot}/totara/webapi/tests/fixtures/schema_diff/local_release.graphqls";
            $local_schema = trim(file_get_contents($old_path));
            if ($local_schema == 'skip') {
                $this->markTestSkipped('Skipped breaking changes test due local schema file request');
            }
        }
        $this->old_schema = BuildSchema::build(file_get_contents($old_path));

        $type = factory::get_instance('dev');
        $this->new_schema = graphql::get_schema($type);
    }

    /**
     * Resets properties created during setUp()
     */
    protected function tearDown(): void {
        $this->old_schema = null;
        $this->new_schema = null;
    }

    /**
     * This method allows the release manager to specify any breaking changes that should be allowed, so that builds
     * continue to pass.
     *
     * It would be better to leave this as an empty array, and update the previously-released schema in the same patch
     * that introduces the breaking change(s), or in the next patch after tests begin to fail.
     *
     * See server/totara/webapi/tests/fixtures/schema_diff/README.md for details on how to update the schema.
     *
     * @return array
     */
    private function get_allowed_changes(): array {
        // This value may only be modified by a release manager.
        return [];
    }

    /**
     * Detect any breaking changes between the old (previously-released) schema and the current development schema.
     *
     * If branch maturity is MATURITY_ALPHA, and TOTARA_DISTRIBUTION_TEST is unset or false, warnings will be emitted.
     * If branch is MATURITY_BETA or higher, this test will fail with a report of the breaking changes.
     */
    public function test_find_breaking_changes(): void {
        global $CFG;

        // Discover version maturity.
        $versionfile = $CFG->dirroot . '/version.php';
        $maturity = null;
        include($versionfile);

        // Diff current schema to previously-released schema.
        $differ = new schema_diff($this->old_schema, $this->new_schema);
        $breaking_changes = $differ->find_breaking_changes();
        $change_notice = 'Breaking GraphQL schema changes detected: ' . print_r($breaking_changes, 1);

        // Only fail this test if we are not in a development branch.
        if ($maturity != MATURITY_ALPHA) {
            $this->assertEquals($this->get_allowed_changes(), $breaking_changes, $change_notice);
        } else if (!defined('TOTARA_DISTRIBUTION_TEST') || TOTARA_DISTRIBUTION_TEST !== true) {
            // Only warn if not in a distribution test environment (in other words, not in Jenkins)
            if (count($breaking_changes) > 0) {
                $this->addWarning($change_notice);
            }
        }
    }
}
