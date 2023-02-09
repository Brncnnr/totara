<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_course
 */

/*
 * Purpose of this page is to give access to list of all categories to users that have
 * special permissions in some subcategory only because the admin tree deals with
 * system context only.
 *
 * Originally this was available from the front page and course/index.php
 * but that changed with Grid catalogue.
 */

require_once("../config.php");
require_once($CFG->libdir.'/adminlib.php');

$sysontext = context_system::instance();

$PAGE->set_context($sysontext);
$PAGE->set_url('/course/categories.php');
$PAGE->set_pagelayout('coursecategory');
$PAGE->set_pagetype('course-index-category');

require_login(null, false);
if (!coursecat::has_capability_on_any('moodle/category:viewhiddencategories')) {
    redirect(new moodle_url('/'));
}
admin_externalpage_setup('coursecategories');

$PAGE->navbar->ignore_active(true);
$PAGE->navbar->add(get_string('coursemgmt', 'admin'), null);

/** @var core_renderer|core_course_renderer $courserenderer */
$courserenderer = $PAGE->get_renderer('core', 'course');

echo $courserenderer->header();
echo $courserenderer->heading(get_string('categories'));

echo $courserenderer->all_categories();

echo $courserenderer->footer();
