<?php
/*
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
 * @author  David Curry <david.curry@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

/**
 * Test functions in totara/core/db/upgradelib.php
 * @group approval_workflow
 */
class totara_core_upgradelib_testcase extends advanced_testcase {


    /**
     * Test that the refresh default category only recreates the misc
     * category as a last resort.
     *
     * covers totara_core_refresh_default_category
     */
    public function test_totara_core_refresh_default_category(): void {
        global $DB;

        // 1) First lets check the expected defaults.
        $cats = $DB->get_records('course_categories', [], 'sortorder');

        $misc = reset($cats);
        $this->assertSame('Miscellaneous', $misc->name);
        $this->assertSame('0', $misc->issystem);
        $this->assertSame((string)(MAX_COURSES_IN_CATEGORY * key($cats)), $misc->sortorder);

        $appr = next($cats);
        $this->assertSame('mod-approval-workflow-category', $appr->name);
        $this->assertSame('1', $appr->issystem);
        $this->assertSame((string)(MAX_COURSES_IN_CATEGORY * key($cats)), $appr->sortorder);

        $perf = next($cats);
        $this->assertSame('performance-activities', $perf->name);
        $this->assertSame('1', $perf->issystem);
        $this->assertSame((string)(MAX_COURSES_IN_CATEGORY * key($cats)), $perf->sortorder);

        $work = next($cats);
        $this->assertSame('Space category', $work->name);
        $this->assertSame('1', $work->issystem);
        $this->assertSame((string)(MAX_COURSES_IN_CATEGORY * key($cats)), $work->sortorder);

        // 2) Then check that calling it with the default categories does nothing.
        totara_core_refresh_default_category();
        $expected_cat_count = count($cats);
        $cat2 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat2);
        foreach ($cats as $key => $cat) {
            if ($new = $cat2[$key]) {
                $this->assertSame($cat->id, $new->id);
                $this->assertSame($cat->name, $new->name);
                $this->assertSame($cat->issystem, $new->issystem);
                $this->assertSame($cat->sortorder, $new->sortorder);
            } else {
                $this->fail("Expected category not found: {$cat->name}");
            }
        }
        $this->assertSame($misc->id, get_config('core', 'defaultrequestcategory'));

        // 3) Then check that calling it after replacing the default misc does nothing.
        $expected_cat_count ++;
        $misc2 = $this->totara_core_insert_category('Misc2', (string)(MAX_COURSES_IN_CATEGORY * $expected_cat_count), null, false);
        $cats[$misc2->id] = $misc2;
        set_config('defaultrequestcategory', $misc2->id);

        totara_core_refresh_default_category();
        $cat3 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat3);
        foreach ($cats as $key => $cat) {
            if ($new = $cat3[$key]) {
                $this->assertSame($cat->id, $new->id);
                $this->assertSame($cat->name, $new->name);
                $this->assertSame($cat->issystem, $new->issystem);
                $this->assertSame($cat->sortorder, $new->sortorder);
            } else {
                $this->fail("Expected category not found: {$cat->name}");
            }
        }
        $this->assertSame($misc2->id, get_config('core', 'defaultrequestcategory'));

        // 4) Then check that calling it with misc deleted after being correctly replaced does nothing.
        unset($cats[$misc->id]);
        $DB->delete_records('course_categories', ['id' => $misc->id]);
        $expected_cat_count--;
        totara_core_refresh_default_category();
        $cat4 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat4);
        foreach ($cats as $key => $cat) {
            if ($new = $cat4[$key]) {
                $this->assertSame($cat->id, $new->id);
                $this->assertSame($cat->name, $new->name);
                $this->assertSame($cat->issystem, $new->issystem);
                $this->assertSame($cat->sortorder, $new->sortorder);
            } else {
                $this->fail("Expected category not found: {$cat->name}");
            }
        }
        $this->assertSame($misc2->id, get_config('core', 'defaultrequestcategory'));

        // 5) - Now check it replaces a bad default with an existing category if possible.
        set_config('defaultrequestcategory', $perf->id);
        totara_core_refresh_default_category();
        $cat4 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat4);
        foreach ($cats as $key => $cat) {
            if ($new = $cat4[$key]) {
                $this->assertSame($cat->id, $new->id);
                $this->assertSame($cat->name, $new->name);
                $this->assertSame($cat->issystem, $new->issystem);
                $this->assertSame($cat->sortorder, $new->sortorder);
            } else {
                $this->fail("Expected category not found: {$cat->name}");
            }
        }
        $this->assertSame($misc2->id, get_config('core', 'defaultrequestcategory'));

        // 6) - Finally check that when all else fails it recreates misc and sets it as default.
        set_config('defaultrequestcategory', $perf->id);
        unset($cats[$misc2->id]);
        $DB->delete_records('course_categories', ['id' => $misc2->id]);
        totara_core_refresh_default_category();
        $cat5 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat5);
        foreach ($cat5 as $key => $cat) {
            if (isset($cats[$key]) && $old = $cats[$key]) {
                $this->assertSame($cat->id, $old->id);
                $this->assertSame($cat->name, $old->name);
                $this->assertSame($cat->issystem, $old->issystem);
                $this->assertSame($cat->sortorder, $old->sortorder);
            } else {
                // This should be our newly recreated misc category.
                $this->assertSame('Miscellaneous', $cat->name);
                $this->assertSame('0', $cat->issystem);
                $this->assertSame($cat->id, get_config('core', 'defaultrequestcategory'));
            }
        }
    }

    /**
     * Note: That programs and certifications sort order does not seem to be affected by
     *       the category sortorder in the same way that courses is.
     *
     * covers totara_core_fix_course_sortorder
     * covers totara_core_fix_category_sortorder
     */
    public function test_totara_core_fix_course_sortorder(): void {
        global $DB;

        // 1) First fetch and check the default categories.
        $cats = $DB->get_records('course_categories', null, 'id ASC');

        $misc = reset($cats);
        $this->assertSame('Miscellaneous', $misc->name);

        $appr = next($cats);
        $this->assertSame('mod-approval-workflow-category', $appr->name);

        $perf = next($cats);
        $this->assertSame('performance-activities', $perf->name);

        $work = next($cats);
        $this->assertSame('Space category', $work->name);

        // 2) Check that calling the function when there are no issues changes nothing.
        totara_core_fix_course_sortorder();
        $expected_cat_count = count($cats);
        $cat2 = $DB->get_records('course_categories', [], 'sortorder');
        $this->assertCount($expected_cat_count, $cat2);
        foreach ($cats as $key => $cat) {
            if ($new = $cat2[$key]) {
                $this->assertSame($cat->id, $new->id);
                $this->assertSame($cat->name, $new->name);
                $this->assertSame($cat->issystem, $new->issystem);
                $this->assertSame($cat->sortorder, $new->sortorder);
            } else {
                $this->fail("Expected category not found: {$cat->name}");
            }
        }

        // 3 - Set up some complicated dummy data with system categories throughout.
        // Misc (aka Top 1)
        //    | -> Course 100
        //    | -> SubCat T1S1
        //             | -> Course 111
        //    | -> SubCat T1S2 (system)
        //    | -> SubCat T1S3
        //             | -> Course 131
        // (system appr)
        // (system perf)
        // (system work)
        // Top 2
        //     | -> Course 2000
        //     | -> SubCat T2S1
        //               | -> Course 2100
        //               | -> SubCat T2S1.1
        //                         | -> Course 2111
        //                         | -> Course 2112
        //               | -> SubCat T2S1.2 (system)
        //               | -> SubCat T2S1.3
        //                         | -> course 2131
        //     | -> SubCat T2S2 (system)
        //     | -> SubCat T2S3
        //              | -> Course 2310
        // Top 3
        //     | -> Course 3.1

        $catcount = $expected_cat_count + 1;

        $t1s1 = $this->totara_core_insert_category('Category T1S1', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $misc, false);
        $c111 = $this->totara_core_insert_course('c111', $t1s1, $t1s1->sortorder + 1);
        $t1s2 = $this->totara_core_insert_category('Category T1S2', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $misc, true);
        $t1s3 = $this->totara_core_insert_category('Category T1S3', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $misc, false);
        $c131 = $this->totara_core_insert_course('c131', $t1s3, $t1s3->sortorder + 1);

        $appr->sortorder = (string)(MAX_COURSES_IN_CATEGORY * $catcount++); // Fix appr sortorder.
        $DB->update_record('course_categories', $appr);
        $perf->sortorder = (string)(MAX_COURSES_IN_CATEGORY * $catcount++); // Fix perf sortorder.
        $DB->update_record('course_categories', $perf);
        $work->sortorder = (string)(MAX_COURSES_IN_CATEGORY * $catcount++); // Fix work sortorder.
        $DB->update_record('course_categories', $work);

        $t2 = $this->totara_core_insert_category('Category Top2', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), null, false);
        $c200 = $this->totara_core_insert_course('c2000', $t2, $t2->sortorder + 1);

        $t2s1 = $this->totara_core_insert_category('Category T2S1', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2, false);
        $c210 = $this->totara_core_insert_course('c2100', $t2s1, $t2s1->sortorder + 1);

        $t2s11 = $this->totara_core_insert_category('SubCat T2S1.1', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2s1, false);
        $c2111 = $this->totara_core_insert_course('c2111', $t2s11, $t2s11->sortorder + 1);
        $c2112 = $this->totara_core_insert_course('c2112', $t2s11, $t2s11->sortorder + 2);

        $t2s12 = $this->totara_core_insert_category('SubCat T2S1.2', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2s1, true);
        $t2s13 = $this->totara_core_insert_category('SubCat T2S1.3', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2s1, false);
        $c2131 = $this->totara_core_insert_course('c2131', $t2s13, $t2s13->sortorder + 1);

        $t2s2 = $this->totara_core_insert_category('Category T2S2', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2, true);

        $t2s3 = $this->totara_core_insert_category('Category T2S3', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), $t2, false);
        $c231 = $this->totara_core_insert_course('c231', $t2s3, $t2s3->sortorder + 1);

        $t3 = $this->totara_core_insert_category('Category Top3', (string)(MAX_COURSES_IN_CATEGORY * $catcount++), null, false);
        $c300 = $this->totara_core_insert_course('c300', $t3, $t3->sortorder + 1);

        // Rebuild context paths.
        context_helper::build_all_paths(false);

        // 3) Check that all system categories have been ordered to the back of their teir.
        // Misc (aka Top 1)
        //    | -> Course 100
        //    | -> SubCat T1S1
        //             | -> Course 111
        //    | -> SubCat T1S3
        //             | -> Course 131
        //    | -> SubCat T1S2 (system)
        // Top 2
        //     | -> Course 2000
        //     | -> SubCat T2S1
        //               | -> Course 2100
        //               | -> SubCat T2S1.1
        //                         | -> Course 2111
        //                         | -> Course 2112
        //               | -> SubCat T2S1.3
        //                         | -> course 2131
        //               | -> SubCat T2S1.2 (system)
        //     | -> SubCat T2S3
        //              | -> Course 2310
        //     | -> SubCat T2S2 (system)
        // Top 3
        //     | -> Course 3.1
        // (system appr)
        // (system perf)
        // (system work)

        // Set up the order we expect to see everything.
        $expectation_count = 1;
        $expectations = [
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $misc,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t1s1,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t1s3,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t1s2,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s1,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s11,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s13,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s12,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s3,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t2s2,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $t3,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $appr,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $perf,
            (string)(MAX_COURSES_IN_CATEGORY * $expectation_count++) => $work
        ];

        totara_core_fix_course_sortorder();
        $cat3 = $DB->get_records('course_categories', [], 'sortorder');
        foreach ($expectations as $sortorder => $cat) {
            $found = array_shift($cat3);

            $this->assertSame($found->name, $cat->name);
            $this->assertEquals($found->sortorder, $sortorder);

            $courses = $DB->get_records('course', ['category' => $found->id], 'sortorder');
            $this->assertCount($cat->coursecount, $courses);
            foreach ($courses as $course) {
                // Check that the courses sort order is within still the expected bounds for the category.
                $this->assertGreaterThan($found->sortorder, $course->sortorder, 'course sortorder before category');
                $this->assertGreaterThan($course->sortorder, $found->sortorder + 10000, 'course sortorder after category'); // Magic number MAX_COURSES_IN_CATEGORY.
            }
        }

        // 4) As a last check, now we have good data, run it again and check nothing changes.
        totara_core_fix_course_sortorder();
        $cat4 = $DB->get_records('course_categories', [], 'sortorder');
        foreach ($expectations as $sortorder => $cat) {
            $found = array_shift($cat4);

            $this->assertSame($found->name, $cat->name);
            $this->assertEquals($found->sortorder, $sortorder);

            $courses = $DB->get_records('course', ['category' => $found->id], 'sortorder');
            $this->assertCount($cat->coursecount, $courses);
            foreach ($courses as $course) {
                // Check that the courses sort order is within still the expected bounds for the category.
                $this->assertGreaterThan($found->sortorder, $course->sortorder, 'course sortorder before category');
                $this->assertGreaterThan($course->sortorder, $found->sortorder + 10000, 'course sortorder after category'); // Magic number MAX_COURSES_IN_CATEGORY.
            }
        }
    }

    /**
     * A quick function to manually insert fairly accurate dummy categories
     * We don't want to use the api since we fixed it to create accurate data
     * and we need to test the upgrade on some moderately broken data
     *
     * @param string  $name      the name of the category
     * @param string  $sortorder the sort order of the category
     * @param object  $parent    the database record for the parent category)
     * @param boolean $system    whether the category is a system one or not)
     */
    private function totara_core_insert_category($name, $sortorder, $parent = null, $system = false) {
        global $DB;

        $category = new \stdClass();
        $category->name = $name;
        $category->idnumber = '';
        $category->sortorder = $sortorder;
        $category->parent = empty($parent) ? 0 : $parent->id;
        $category->depth = empty($parent) ? 1 : $parent->depth + 1;
        $category->visible = 1;
        $category->description = '';
        $category->descriptionformat = 0;
        $category->issystem = $system ? '1' : '0';
        $category->coursecount = 0;

        $category->id = (string) $DB->insert_record('course_categories', $category);
        $category->path = empty($parent) ? "/{$category->id}" : "{$parent->path}/{$category->id}";
        $DB->update_record('course_categories', $category);

        return $category;
    }

    /**
     * A quick function to manually insert reasonably accurate dummy courses
     * We don't want to use the api since we fixed it to create accurate data
     * and we need to test the upgrade on some moderately broken data
     *
     * @param string $name
     * @param object $category  The database object of the category we are inserting the course into
     * @param string $sortorder The sortorder of the course
     */
    private function totara_core_insert_course($name, &$category, $sortorder) {
        global $DB;

        $course = new \stdClass();
        $course->fullname = $name;
        $course->shortname = $name;
        $course->idnumber = '';
        $course->summary = '';
        $course->summaryformat = 0;
        $course->category = $category->id;
        $course->timecreated = time();
        $course->timemodified = time();
        $course->sortorder = $sortorder;

        $course->id = $DB->insert_record('course', $course);

        // Not sure we really need to but lets check this.
        $category->coursecount++;
        $DB->update_record('course_categories', $category);

        return $course;
    }
}
