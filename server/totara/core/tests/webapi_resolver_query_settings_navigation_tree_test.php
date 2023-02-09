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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

use core_phpunit\testcase;
use totara_core\tui\tree\tree_node;
use totara_core\webapi\resolver\query\settings_navigation_tree;
use totara_core\webapi\resolver\type\tui_tree_node;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_core_webapi_resolver_query_settings_navigation_tree_test extends testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'totara_core_settings_navigation_tree';

    private const PAGE_URL = '/index.php?id=4';

    /** @var int */
    private $course_context;

    /** @var int */
    private $module_context;

    /** @var int */
    private $category_context;

    /** @var int */
    private $system_context;

    protected function setUp(): void {
        parent::setUp();
        $generator = \core\testing\generator::instance();

        $this->system_context = context_system::instance();

        $category = $generator->create_category();
        $this->category_context = context_coursecat::instance($category->id);

        $course = $generator->create_course(['categoryid' => $category->id]);
        $this->course_context = context_course::instance($course->id);

        $module = $generator->create_module('assign', ['course' => $course]);
        $this->module_context = context_module::instance($module->cmid);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->system_context = $this->category_context = $this->course_context = $this->module_context = null;
    }

    public function test_tree_for_category_context(): void {
        self::setAdminUser();

        $expected_ids = [
            'categorysettings' => [
                'edit' => [],
                'addsubcat' => [],
                'roles' => [],
                'permissions' => [],
                'checkpermissions' => [],
                'cohort' => [],
                'filters' => [],
                'restorecourse' => [],
            ],
        ];

        $result = $this->resolve_query($this->category_context);
        $this->assert_tree_structure_same($expected_ids, $this->simplify_tree($result['trees']));
    }

    public function test_resolve_tree_for_course_context(): void {
        self::setAdminUser();

        $expected_ids = [
            'courseadmin' => [
                'editsettings' => [],
                'turneditingonoff' => [],
                'users' => [
                    'review' => [],
                    'manageinstances' => null,
                    'groups' => [],
                    'override' => [
                        'permissions' => [],
                    ],
                    'otherusers' => [],
                ],
                'coursereports' => null,
                'grades' => [],
                'gradebooksetup' => [],
                'outcomes' => [],
                'coursebadges' => [
                    'coursebadges' => [],
                    'newbadge' => [],
                ],
                'backup' => [],
                'restore' => [],
                'import' => [],
                'reset' => [],
                'questionbank' => [
                    'questions' => [],
                    'categories' => [],
                    'import' => [],
                    'export' => [],
                ],
                'switchroleto' => null,
            ],
        ];

        $result = $this->resolve_query($this->course_context);
        $this->assert_tree_structure_same($expected_ids, $this->simplify_tree($result['trees']));
    }

    public function test_resolve_tree_for_module_context(): void {
        self::setAdminUser();

        $expected_ids = [
            'modulesettings' => [
                'modedit' => [],
                'roleassign' => [],
                'roleoverride' => [],
                'rolecheck' => [],
                'filtermanage' => [],
                'logreport' => [],
                'backup' => [],
                'restore' => [],
            ],
            'courseadmin' => [
                'editsettings' => [],
                'turneditingonoff' => [],
                'users' => [
                    'review' => [],
                    'manageinstances' => null,
                    'groups' => [],
                    'override' => [
                        'permissions' => [],
                    ],
                    'otherusers' => [],
                ],
                'coursereports' => null,
                'grades' => [],
                'gradebooksetup' => [],
                'outcomes' => [],
                'coursebadges' => [
                    'coursebadges' => [],
                    'newbadge' => [],
                ],
                'backup' => [],
                'restore' => [],
                'import' => [],
                'reset' => [],
                'questionbank' => [
                    'questions' => [],
                    'categories' => [],
                    'import' => [],
                    'export' => [],
                ],
                'switchroleto' => null,
            ],
        ];

        $result = $this->resolve_query($this->module_context);
        $this->assert_tree_structure_same($expected_ids, $this->simplify_tree($result['trees']));
        $this->assertEquals(['modulesettings'], $result['open_ids']);
    }

    public function test_resolve_tree_for_module_context_as_enrolled_learner(): void {
        $user = \core\testing\generator::instance()->create_user();
        self::setUser($user);
        \core\testing\generator::instance()->enrol_user($user->id, $this->course_context->instanceid);

        $expected_ids = [
            'courseadmin' => [
                'grades' => [],
            ],
        ];

        $result = $this->resolve_query($this->module_context);
        $this->assert_tree_structure_same($expected_ids, $this->simplify_tree($result['trees']));
        $this->assertEquals([], $result['open_ids']);
    }

    /**
     * Checks in detail that the actual nodes themselves have their data mapped correctly.
     */
    public function test_resolve_settings_tree_type_values(): void {
        self::setAdminUser();

        $test_page_url = '/lib/womenslib.php?id=1961';

        $result = $this->resolve_query($this->module_context, $test_page_url);
        $resolved_nodes = [];
        foreach ($result['trees'] as $node) {
            $resolved_nodes[] = $this->resolve_node($node);
        }

        $this->assertCount(2, $resolved_nodes);

        // Module root node
        $module_root = $resolved_nodes[0];
        $module_children = $module_root['children'];
        unset($module_root['children']);
        $this->assertEquals([
            'id' => 'modulesettings',
            'label' => get_string('pluginadministration', 'mod_assign'),
            'linkUrl' => null,
        ], $module_root);

        // Module setting - edit settings
        $this->assertEquals([
            'id' => 'modulesettings/modedit',
            'label' => get_string('editsettings'),
            'linkUrl' => "https://www.example.com/moodle/course/modedit.php?update={$this->module_context->instanceid}&return=1",
            'children' => [],
        ], $module_children[0]);

        // Course root node
        $course_root = $resolved_nodes[1];
        $course_children = $course_root['children'];
        unset($course_root['children']);
        $this->assertEquals([
            'id' => 'courseadmin',
            'label' => get_string('courseadministration'),
            'linkUrl' => null,
        ], $course_root);

        // Course setting - edit settings
        $this->assertEquals([
            'id' => 'courseadmin/editsettings',
            'label' => get_string('editsettings'),
            'linkUrl' => "https://www.example.com/moodle/course/edit.php?id={$this->course_context->instanceid}",
            'children' => [],
        ], $course_children[0]);

        // Course setting - turn editing on
        $turn_editing_on = $course_children[1];
        $this->assertEquals('courseadmin/turneditingonoff', $turn_editing_on['id']);
        $this->assertEquals(get_string('turneditingon'), $turn_editing_on['label']);
        $this->assertEmpty($turn_editing_on['children']);
        // The return param of the link for the turn editing on/off button should contain the page_url inputted to the query.
        $this->assertEquals($test_page_url, (new moodle_url($turn_editing_on['linkUrl']))->get_param('return'));
    }

    public function test_resolve_when_not_logged_in(): void {
        $this->expectException(require_login_exception::class);
        $this->resolve_query($this->module_context);
    }

    public function test_resolve_invalid_context(): void {
        self::setAdminUser();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('nopermissions', 'error'));
        $this->resolve_graphql_query(self::QUERY, ['context_id' => -1]);
    }

    public function test_tree_for_system_context_is_empty(): void {
        self::setAdminUser();

        $result = $this->resolve_query($this->system_context);
        $actual_ids = $this->simplify_tree($result['trees']);
        $this->assertEmpty($actual_ids);
        $this->assertEmpty($result['open_ids']);
    }

    public function test_tree_for_system_context_with_and_without_legacy_setting_enabled(): void {
        global $CFG;
        self::setAdminUser();

        // With an actual admin page it will create a root node in the navigation tree
        // as opposed to a non admin page where it would just create a dummy sysadmin node.
        // Both should be filtered out by the query if the legacy navigation is disabled.
        $result = $this->resolve_query($this->system_context, '/admin/user.php');
        $actual_ids = $this->simplify_tree($result['trees']);
        $this->assertEmpty($actual_ids);

        $result = $this->resolve_query($this->system_context);
        $actual_ids = $this->simplify_tree($result['trees']);
        $this->assertEmpty($actual_ids);

        $CFG->legacyadminsettingsmenu = true;

        $result = $this->resolve_query($this->system_context, '/admin/user.php');
        $actual_ids = $this->simplify_tree($result['trees']);
        $this->assertNotEmpty($actual_ids);
        $this->assertArrayHasKey('root', $actual_ids);
        $this->assertArrayHasKey('systeminformation', $actual_ids['root']);

        $result = $this->resolve_query($this->system_context);
        $actual_ids = $this->simplify_tree($result['trees']);
        $this->assertNotEmpty($actual_ids);
        $this->assertArrayHasKey('root', $actual_ids);
        $this->assertArrayHasKey('systeminformation', $actual_ids['root']);
        $this->assertEquals(['root'], $result['open_ids']);
    }

    public function test_string_formatting(): void {
        self::setAdminUser();

        // Include javascript to test that XSS risk is handled, and include ampersand (&) to ensure HTML entities are handled.
        $course = self::getDataGenerator()->create_course(['shortname' => 'Test<script>Bad!</script>Course & Module']);
        self::getDataGenerator()->enrol_user(get_admin()->id, $course->id);
        $course_context = context_course::instance($course->id);

        $result = $this->resolve_query($course_context);

        /** @var tree_node $root_node */
        $root_node = $result['trees'][0];
        $node = $root_node->find_node('courseadmin/unenrolself');

        // String should be formatted both after AND before going through the graphQL type
        $expected_label = get_string('unenrolme', 'core_enrol', 'TestBad!Course & Module');
        $this->assertEquals($expected_label, $node->get_label());
        $this->assertEquals(
            $expected_label,
            $this->resolve_graphql_type('totara_core_tui_tree_node', 'label', $node)
        );
    }

    public function test_debugging_notice_if_tree_is_too_deep(): void {
        self::setAdminUser();

        // Create a tree that is 7 nodes deep (max is 6) in order to trigger debugging notice
        $target_depth = 7;
        $root = null;
        for ($current_depth = 1, $parent = null; $current_depth <= $target_depth; $current_depth++) {
            $child = navigation_node::create($current_depth);
            $child->key = $current_depth;
            if ($parent !== null) {
                $parent->add_node($child);
            }
            if ($root === null) {
                $root = $parent;
            }
            $parent = $child;
        }

        $method = new ReflectionMethod(settings_navigation_tree::class, 'map_tree');
        $method->setAccessible(true);

        $method->invoke(null, context_system::instance(), $root);
        self::assertDebuggingCalled(
            "The settings navigation tree node with ID '1/2/3/4/5/6/7' is deeper than the max supported depth of 6, and may not be resolved correctly.",
            DEBUG_DEVELOPER
        );
    }

    public function test_external_page_url_specified(): void {
        self::setAdminUser();

        $invalid_page_url = "https://example.com/index.php";
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid page_url specified: $invalid_page_url");
        $this->resolve_query($this->course_context, $invalid_page_url);
    }

    /**
     * @param context $context
     * @param string|null $url
     * @return array
     */
    private function resolve_query(context $context, string $url = null): array {
        return $this->resolve_graphql_query(self::QUERY, [
            'context_id' => $context->id,
            'page_url' => $url ?? self::PAGE_URL,
        ]);
    }

    /**
     * Make sure that the resolved settings tree has the options that we would expect.
     *
     * @param array $expected
     * @param array $actual
     */
    private function assert_tree_structure_same(array $expected, array $actual): void {
        foreach ($expected as $expected_id => $expected_children) {
            $this->assertArrayHasKey($expected_id, $actual, "Couldn't find a settings node with ID '$expected_id' in the tree.");
            if ($expected_children === null) {
                continue;
            }
            if (empty($expected_children)) {
                $this->assertEmpty($actual[$expected_id], "Expected settings node with ID '$expected_id' to not have any children");
            }

            $this->assert_tree_structure_same($expected_children, $actual[$expected_id]);
        }
    }

    /**
     * Convert tree nodes into a readable recursive structure of [ ID => child IDs]
     * This allows us to easily check that the correct settings nodes are present.
     *
     * @param tree_node[] $nodes
     * @param tree_node|null $parent
     * @return array
     */
    private function simplify_tree(array $nodes, tree_node $parent = null): array {
        $ids = [];
        foreach ($nodes as $node) {
            $node_id = $node->get_id();
            if ($parent) {
                $node_id = str_replace($parent->get_id() . '/', '', $node_id);
            }

            $ids[$node_id] = $this->simplify_tree($node->get_children(), $node);
        }
        return $ids;
    }

    /**
     * Recursively resolves the tree using the GraphQL type fields.
     * This will return basically the same result as a query via Apollo would.
     *
     * @param tree_node $tree_node
     * @return array
     */
    private function resolve_node(tree_node $tree_node): array {
        $type_name = $this->get_graphql_name(tui_tree_node::class);
        $result = [
            'id' => $this->resolve_graphql_type($type_name, 'id', $tree_node),
            'label' => $this->resolve_graphql_type($type_name, 'label', $tree_node),
            'linkUrl' => $this->resolve_graphql_type($type_name, 'linkUrl', $tree_node),
        ];

        $children = $this->resolve_graphql_type($type_name, 'children', $tree_node);
        $result['children'] = [];
        foreach ($children as $child) {
            $result['children'][] = $this->resolve_node($child);
        }

        return $result;
    }

}
