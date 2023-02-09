<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 */

namespace core_course\user_learning;

use core_course\data_provider\course as course_provider;
use core_course\entity\filter\course_filter_factory;
use core\output\popover;
use totara_core\data_provider\provider;
use totara_core\user_learning\item_base;
use totara_core\user_learning\item_has_dueinfo;
use totara_core\user_learning\item_has_image;
use totara_core\user_learning\item_has_progress;
use totara_core\user_learning\designation_primary;
/** @var \core_config $CFG */
require_once($CFG->libdir . '/completionlib.php');

class item extends item_base implements item_has_progress, item_has_dueinfo, item_has_image {

    use designation_primary;

    /**
     * True if this course can be completed, false if not, null if not yet loaded/known.
     * @var bool|null
     */
    protected $progress_canbecompleted = null;

    /**
     * True if completion criteria specified, false if not, null if not yet loaded/known.
     * @var bool|null
     */
    protected $progress_hascompletioncriteria = null;

    /**
     * The users progress as a percentage.
     * @var int
     */
    protected $progress_percentage;

    /**
     * Description of the users progress.
     * @var string
     */
    protected $progress_summary;

    /**
     * True if the user has completed this course, false otherwise.
     * @var bool
     */
    protected $progress_complete;

    /**
     * Progress information
     * @var progressinfo
     */
    protected $progressinfo;

    /**
     * Course completion status for a particular user/course
     * @var completion_completion
     */
    protected $completion;

    /**
     * Gets all course learning items for the given user.
     *
     * @param \stdClass|int $userorid A user object or user ID
     * @return array An array of learning object of type item
     */
    public static function all($userorid) {
        $items = [];
        $user = self::resolve_user($userorid);

        $fields = ['id', 'category', 'sortorder',
            'shortname', 'fullname', 'idnumber',
            'summary', 'summaryformat', 'startdate',
            'visible', 'defaultgroupingid',
            'groupmode', 'groupmodeforce', 'duedate'
        ];


        foreach (enrol_get_all_users_courses($user->id, true, $fields) as $course) {
            $class = get_called_class();
            $items[] = new $class($user, $course);
        }
        return $items;
    }

    /**
     * @inheritDocs
     */
    public static function current($userorid): array {
        $items = [];
        $user = self::resolve_user($userorid);

        $fields = ['id', 'category', 'sortorder',
            'shortname', 'fullname', 'idnumber',
            'summary', 'summaryformat', 'startdate',
            'visible', 'defaultgroupingid',
            'groupmode', 'groupmodeforce',
            'cacherev', 'enablecompletion'
        ];

        $non_completed_courses = enrol_get_non_completed_courses_by_userid(
            $user->id,
            true,
            $fields,
            'visible DESC,sortorder ASC',
            0,
            0,
            'totara_program'
        );

        foreach ($non_completed_courses as $course) {
            $class = get_called_class();
            $items[] = new $class($user, $course);
        }
        return $items;
    }

    /**
     * Gets a single course learning item for a give user.
     *
     * @param \stdClass|int $userorid A user object of ID
     * @param item|\stdClass|int $itemorid A course object or ID
     * @return item_base A learning item object for the course
     */
    public static function one($userorid, $itemorid) {
        if (is_object($itemorid) && isset($itemorid->id)) {
            $course = $itemorid;
        } else {
            $course = get_course($itemorid);
        }

        // Late static binding is essential here as other classes
        // extend this on and rely on this function.
        $class = get_called_class();
        $item = new $class($userorid, $course);
        return $item;
    }

    /**
     * Get the context for the course item
     *
     * @return integer The course context level for the course.
     */
    public static function get_context_level() {
        return CONTEXT_COURSE;
    }

    /**
     * Get progress completion
     *
     * @return bool course complete
     */
    public function is_complete() {
        $this->ensure_completion_loaded();

        return $this->progress_complete;
    }

    /**
     * Maps data from the course properties to the item object
     *
     * @param \stdClass $data A course object
     */
    protected function map_learning_item_record_data(\stdClass $data) {
        $this->id = $data->id;
        $this->fullname = $data->fullname;
        $this->shortname = $data->shortname;
        if (isset($data->summary)) {
            $this->description = $data->summary;
        }
        if (isset($data->summaryformat)) {
            $this->description_format = $data->summaryformat;
        }
        $this->image = $this->get_image();

        $this->url_view = new \moodle_url('/course/view.php', array('id' => $this->id));

        $this->viewable = $data->viewable ?? totara_course_is_viewable($data->id);
    }

    /**
     * Check if a course can be completed.
     *
     * @return bool True if a course can be completed
     */
    public function can_be_completed() {
        $this->ensure_completion_loaded();
        return $this->progress_canbecompleted;
    }

    /**
     * Check if completion criteria specified for the course
     *
     * @return bool
     */
    public function has_completion_criteria() {
        $this->ensure_completion_loaded();
        return $this->progress_hascompletioncriteria;
    }

    /**
     * If completion is enable for the site and course then
     * load the completion and progress info
     *
     * progress_canbecompleted is set the first time this is run
     * so if it is not null then we already have the data we need.
     */
    protected function ensure_completion_loaded() {

        if ($this->progress_canbecompleted === null) {
            $this->progress_canbecompleted = false;
            $this->progress_hascompletioncriteria = false;
            $this->progress_summary = new \lang_string('statusnottracked', 'completion');
            $this->progress_complete = false;

            if (!\completion_info::is_enabled_for_site()) {
                // Completion is disabled at the site level.
                return;
            }

            // Get course completion data.
            // We'll use the learningitemrecord passed in during construction.
            $info = new \completion_info($this->learningitemrecord);
            if (!$info->is_enabled()) {
                // Completion is disabled at the course level.
                return;
            }

            // The user may be enrolled via the program only or was marked completed via rpl.
            // In this case it may not be tracked for completion, but we still want to show progress

            $this->progress_canbecompleted = true;
            // But they may not already be complete.
            $this->progress_complete = false;
            $this->progress_hascompletioncriteria = $info->has_criteria();
            $this->progress_summary = new \lang_string('statusnocriteria', 'completion');

            $this->completion = new \completion_completion(['userid' => $this->user->id, 'course' => $this->id]);
            $status = \completion_completion::get_status($this->completion);
            switch ($status) {
                case 'complete':
                case 'completeviarpl':
                    $this->progress_complete = true;
                    break;
                default:
                    // If there is no completioncriteria, display 'No criteria'
                    if (!$this->progress_hascompletioncriteria) {
                        $status = null;
                    }
                    break;
            }

            $this->progressinfo = $this->completion->get_progressinfo();
            // Default to 0 if not tracked
            $this->progress_percentage = (int)$this->completion->get_percentagecomplete();

            if (empty($status)) {
                if ($this->progress_hascompletioncriteria) {
                    $this->progress_summary = new \lang_string('notyetstarted', 'completion');
                }
            } else {
                $this->progress_summary = new \lang_string($status, 'completion');
            }

            $this->duedate = $this->completion->duedate;
        }
    }

    /**
     * Checks completion is loaded and returns the percentage complete
     *
     * @return integer The percentage complete
     */
    public function get_progress_percentage() {
        $this->ensure_completion_loaded();
        return $this->progress_percentage;
    }

    /**
     * Checks completion is loaded and returns the progress summary.
     *
     * @return string|null
     */
    public function get_progress_summary(): ?string {
        $this->ensure_completion_loaded();
        return $this->progress_summary;
    }

    /**
     * Export progress information to display in template
     *
     * @return \stdClass Object containing progress info
     */
    public function export_progress_for_template() {
        global $OUTPUT;

        $this->ensure_completion_loaded();

        $record = new \stdClass;
        $record->summarytext = (string)$this->progress_summary;
        if ($this->progress_canbecompleted && $this->progress_hascompletioncriteria) {
            $pbar = new \static_progress_bar('', '0', false, $this->fullname);
            $pbar->set_progress($this->progress_percentage);
            $detaildata = $this->completion->export_completion_criteria_for_template();
            if (!empty($detaildata)) {
                $pbar->add_popover(popover::create_from_template('totara_core/course_completion_criteria', $detaildata));
            }
            $record->pbar = $pbar->export_for_template($OUTPUT);
        }

        return $record;
    }

    public function item_has_duedate() {
        return true;
    }

    /**
     * Returns the image url of this program.
     *
     * @return string
     */
    public function get_image() {
        $course = get_course($this->id);
        return course_get_image($course)->out();
    }

    /**
     * Returns the component that owns this user learning instance.
     * @return string
     */
    public function get_component() {
        return 'core_course';
    }

    /**
     * Returns the component name.
     *
     * @return string
     */
    public function get_component_name() : string {
        return get_string('course');
    }

    /**
     * Returns the type of this user learning instance.
     * @return string
     */
    public function get_type() {
        return 'course';
    }

    /**
     * @inheritDoc
     */
    public static function get_data_provider(): ?provider {
        return course_provider::create(
            new course_filter_factory()
        );
    }

    /**
     * Export due date data to display in template
     *
     * @return stdClass Object containing due date info
     */
    public function export_dueinfo_for_template() {
        $this->ensure_duedate_loaded();

        // If there is not duedate then we can't create the date for display.
        if ((int)$this->duedate <= 0) {
            return;
        }

        $now = time();

        $dueinfo = new \stdClass();

        // Date for tooltip.
        $duedateformat = get_string('strftimedatetimeon', 'langconfig');
        $duedateformattedtooltip = userdate($this->duedate, $duedateformat);

        $duedateformatted = userdate($this->duedate, get_string('strftimedateshorttotara', 'langconfig'));
        if ($now > $this->duedate) {
            // Overdue.
            $dueinfo->duetext = get_string('userlearningoverduesincex', 'totara_core', $duedateformatted);
            $dueinfo->tooltip = get_string('userlearningoverduesincextooltip', 'totara_core', $duedateformattedtooltip);
        } else {
            // Due.
            $dueinfo->duetext = get_string('userlearningdueonx', 'totara_core', $duedateformatted);
            $dueinfo->tooltip = get_string('courseduex', 'totara_core', $duedateformattedtooltip);
        }

        return $dueinfo;
    }

    /**
     * Returns the due date info for this item
     *
     * @return \stdClass Object containing due info (duetext and tooltip).
     */
    public function get_dueinfo() {
        return $this->dueinfo;
    }

    /**
     * Load duedate if it isn't already
     */
    public function ensure_duedate_loaded() {
        if ($this->duedate === null) {
            $this->ensure_completion_loaded();
        }
    }
}