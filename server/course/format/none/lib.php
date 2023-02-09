<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package format_none
 */
defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/course/format/lib.php");

/**
 * The class \format_none should be used as a new course format within new extended container pretty much.
 */
class format_none extends \format_base {
    /**
     * Please use the container one. This API should not belong to the course_format.
     * @return bool
     */
    final public function has_view_page() {
        return false;
    }

    /**
     * @param int|stdClass $section
     * @return string
     */
    public function get_section_name($section) {
        $number = $section;
        if (is_object($section)) {
            $number = $section->section;
        }

        return get_string('sectionname', 'format_none', $number);
    }

    /**
     * Must be overridden at the child level.
     *
     * @param int|stdClass $section
     * @return string
     */
    public function get_default_section_name($section) {
        return $this->get_section_name($section);
    }

    /**
     * Moving section should not be a part of the format. Please use the container API instead.
     */
    final public function ajax_section_move() {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * Most likely this should not be used within a new container. This function is being called in course_edit_form.
     * Which the new container should be using different form.
     *
     * @param array $data
     * @param array $files
     * @param array $errors
     */
    final public function edit_form_validation($data, $files, $errors) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * @param mixed $action
     * @param array $customdata
     * @return moodleform|void
     */
    final public function editsection_form($action, $customdata = array()) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * This is most likely for single activity page. If you want to do a single activity page within a new
     * container. Start doing it at the view page instead.
     *
     * @param moodle_page $page
     */
    final public function page_set_course(moodle_page $page) {
        return;
    }

    /**
     * // No point to set the cm for the format. Again it seems to be for single activity format only
     * @param moodle_page $page
     */
    final public function page_set_cm(moodle_page $page) {
        return;
    }

    /**
     * @param int|section_info|stdClass $section
     * @return bool|void
     */
    final public function is_section_current($section) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * No point to use this within new container.
     *
     * @param section_info $section
     * @param bool $available
     * @param string $availableinfo
     */
    final public function section_get_available_hook(section_info $section, &$available, &$availableinfo) {
        return;
    }

    /**
     * Format should not be able to delete any section at all. Let the course_modinfo or section do it
     * @param int|section_info|stdClass $section
     * @return bool
     */
    final public function can_delete_section($section) {
        return false;
    }

    /**
     * @param int|section_info|stdClass $section
     * @param bool $forcedeleteifnotempty
     * @return bool|void
     * @throws coding_exception
     */
    final public function delete_section($section, $forcedeleteifnotempty = false) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * This function has been blocked, because we do not want people to manipulate the sections of the course.
     *
     * @param section_info|stdClass $section
     * @param bool $linkifneeded
     * @param null $editable
     * @param null $edithint
     * @param null $editlabel
     */
    final public function inplace_editable_render_section_name($section, $linkifneeded = true, $editable = null,
                                                               $edithint = null, $editlabel = null) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * @param stdClass $section
     * @param string $itemtype
     * @param mixed $newvalue
     */
    final public function inplace_editable_update_section_name($section, $itemtype, $newvalue) {
        throw $this->produce_function_exception(__FUNCTION__);
    }

    /**
     * @param string $fx
     * @return \coding_exception
     */
    private function produce_function_exception(string $fx): \coding_exception {
        return new \coding_exception("Function '{$fx}' is not supported");
    }

    /**
     * Should be override by the container ?
     * @return array
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => []
        ];
    }
}