<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/coursecatlib.php');

class core_course_cat_lib_testcase extends advanced_testcase {

    /*
     * Test creating a course category with the data generator.
     */
    public function test_create_category_with_generator() {
        global $DB;
        // Create a new course category and check it exists.
        $record = $this->getDataGenerator()->create_category();
        $exists = $DB->record_exists('course_categories', array('id' => $record->id));
        // Assert the existance of the record.
        $this->assertTrue($exists);
    }

    public function test_preload_category_courses_and_counts() {
        global $CFG;

        $default = coursecat::get($CFG->defaultrequestcategory)->id;
        $sortbit = serialize(['sortorder' => 1]);

        $cache = cache::make('core', 'coursecat');
        self::assertFalse($cache->has('l-'. $default. '--'. $sortbit));
        self::assertFalse($cache->has('lcnt-'.$default.'-'));

        \coursecat::preload_category_courses_and_counts([$default]);

        self::assertSame(0, $cache->get('lcnt-'.$default.'-'));
        self::assertSame([], $cache->get('l-'. $default. '--'. $sortbit));

        $generator = $this->getDataGenerator();
        $cat1 = $generator->create_category();
        $cat2 = $generator->create_category();
        $sub1 = $generator->create_category(array('parent' => $cat1->id));
        $sub2 = $generator->create_category(array('parent' => $cat1->id));
        $sub3 = $generator->create_category(array('parent' => $sub1->id));
        $course1 = $generator->create_course(['category' => $cat2->id]);
        $course2 = $generator->create_course(['category' => $sub3->id]);

        \coursecat::preload_category_courses_and_counts([$default]);

        self::assertSame(0, $cache->get('lcnt-'.$default.'-'));
        self::assertSame([], $cache->get('l-'. $default. '--'. $sortbit));

        $cache->purge();

        \coursecat::preload_category_courses_and_counts([$default, $cat1->id, $cat2->id]);

        self::assertSame(0, $cache->get('lcnt-'.$default.'-'));
        self::assertSame([], $cache->get('l-'. $default. '--'. $sortbit));
        self::assertSame(0, $cache->get('lcnt-'.$cat1->id.'-'));
        self::assertSame([], $cache->get('l-'. $cat1->id. '--'. $sortbit));
        self::assertSame('1', $cache->get('lcnt-'.$cat2->id.'-'));
        self::assertSame([$course1->id], $cache->get('l-'. $cat2->id. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$sub1->id.'-'));
        self::assertFalse($cache->get('l-'. $sub1->id. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$sub2->id.'-'));
        self::assertFalse($cache->get('l-'. $sub2->id. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$sub3->id.'-'));
        self::assertFalse($cache->get('l-'. $sub3->id. '--'. $sortbit));

        $cache->purge();

        \coursecat::preload_category_courses_and_counts([$sub1->id, $sub3->id]);

        self::assertFalse($cache->get('lcnt-'.$default.'-'));
        self::assertFalse($cache->get('l-'. $default. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$cat1->id.'-'));
        self::assertFalse($cache->get('l-'. $cat1->id. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$cat2->id.'-'));
        self::assertFalse($cache->get('l-'. $cat2->id. '--'. $sortbit));
        self::assertSame(0, $cache->get('lcnt-'.$sub1->id.'-'));
        self::assertSame([], $cache->get('l-'. $sub1->id. '--'. $sortbit));
        self::assertFalse($cache->get('lcnt-'.$sub2->id.'-'));
        self::assertFalse($cache->get('l-'. $sub2->id. '--'. $sortbit));
        self::assertSame('1', $cache->get('lcnt-'.$sub3->id.'-'));
        self::assertSame([$course2->id], $cache->get('l-'. $sub3->id. '--'. $sortbit));
    }
}
