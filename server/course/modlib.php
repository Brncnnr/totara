<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions specific to course/modedit.php and course API functions.
 * The course API function calling them are course/lib.php:create_module() and update_module().
 * This file has been created has an alternative solution to a full refactor of course/modedit.php
 * in order to create the course API functions.
 *
 * @copyright 2013 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Add course module.
 *
 * The function does not check user capabilities.
 * The function creates course module, module instance, add the module to the correct section.
 * It also trigger common action that need to be done after adding/updating a module.
 *
 * @param object $moduleinfo the moudle data
 * @param object $course the course of the module
 * @param object $mform this is required by an existing hack to deal with files during MODULENAME_add_instance()
 * @return object the updated module info
 *
 * @deprecated since Totara 13.0
 */
function add_moduleinfo($moduleinfo, $course, $mform = null) {
//    debugging(
//        "The function 'add_moduleinfo' has been deprecated, please use " .
//        "\container_course\course::add_module instead",
//        DEBUG_DEVELOPER
//    );

    $container = \core_container\factory::from_record($course);
    $module = $container->add_module($moduleinfo, $mform);

    // For backward compatibility. We need to update the source of truth.
    // Update whatever from the database, and whatever had been add additionally to the module.
    $record = $module->to_record();

    $properties = get_object_vars($record);

    // This is for backwards compatibility where the 'section' should be pointing to the
    // section number not the section id.
    $properties['section'] = $module->get_section()->get_section_number();

    foreach ($properties as $property => $value) {
        $moduleinfo->{$property} = $value;
    }

    return $moduleinfo;
}

/**
 * Hook for plugins to take action when a module is created or updated.
 *
 * @param stdClass $moduleinfo the module info
 * @param stdClass $course the course of the module
 *
 * @return stdClass moduleinfo updated by plugins.
 */
function plugin_extend_coursemodule_edit_post_actions($moduleinfo, $course) {
    $callbacks = get_plugins_with_function('coursemodule_edit_post_actions', 'lib.php');
    foreach ($callbacks as $type => $plugins) {
        foreach ($plugins as $plugin => $pluginfunction) {
            $moduleinfo = $pluginfunction($moduleinfo, $course);
        }
    }
    return $moduleinfo;
}

/**
 * Common create/update module module actions that need to be processed as soon as a module is created/updaded.
 * For example:create grade parent category, add outcomes, rebuild caches, regrade, save plagiarism settings...
 * Please note this api does not trigger events as of MOODLE 2.6. Please trigger events before calling this api.
 *
 * @param object $moduleinfo the module info
 * @param object $course the course of the module
 *
 * @return object moduleinfo update with grading management info
 */
function edit_module_post_actions($moduleinfo, $course) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $modcontext = context_module::instance($moduleinfo->coursemodule);
    $hasgrades = plugin_supports('mod', $moduleinfo->modulename, FEATURE_GRADE_HAS_GRADE, false);
    $hasoutcomes = plugin_supports('mod', $moduleinfo->modulename, FEATURE_GRADE_OUTCOMES, true);

    // Sync idnumber with grade_item.
    if ($hasgrades && $grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$moduleinfo->modulename,
                 'iteminstance'=>$moduleinfo->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
        $gradeupdate = false;
        if ($grade_item->idnumber != $moduleinfo->cmidnumber) {
            $grade_item->idnumber = $moduleinfo->cmidnumber;
            $gradeupdate = true;
        }
        if (isset($moduleinfo->gradepass) && $grade_item->gradepass != $moduleinfo->gradepass) {
            $grade_item->gradepass = $moduleinfo->gradepass;
            $gradeupdate = true;
        }
        if ($gradeupdate) {
            $grade_item->update();
        }
    }

    if ($hasgrades) {
        $items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$moduleinfo->modulename,
                                         'iteminstance'=>$moduleinfo->instance, 'courseid'=>$course->id));
    } else {
        $items = array();
    }

    // Create parent category if requested and move to correct parent category.
    if ($items and isset($moduleinfo->gradecat)) {
        if ($moduleinfo->gradecat == -1) {
            $grade_category = new grade_category();
            $grade_category->courseid = $course->id;
            $grade_category->fullname = $moduleinfo->name;
            $grade_category->insert();
            if ($grade_item) {
                $parent = $grade_item->get_parent_category();
                $grade_category->set_parent($parent->id);
            }
            $moduleinfo->gradecat = $grade_category->id;
        }

        foreach ($items as $itemid=>$unused) {
            $items[$itemid]->set_parent($moduleinfo->gradecat);
            if ($itemid == $grade_item->id) {
                // Use updated grade_item.
                $grade_item = $items[$itemid];
            }
        }
    }

    require_once($CFG->libdir.'/grade/grade_outcome.php');
    // Add outcomes if requested.
    if ($hasoutcomes && $outcomes = grade_outcome::fetch_all_available($course->id)) {
        $grade_items = array();

        // Outcome grade_item.itemnumber start at 1000, there is nothing above outcomes.
        $max_itemnumber = 999;
        if ($items) {
            foreach($items as $item) {
                if ($item->itemnumber > $max_itemnumber) {
                    $max_itemnumber = $item->itemnumber;
                }
            }
        }

        foreach($outcomes as $outcome) {
            $elname = 'outcome_'.$outcome->id;

            if (property_exists($moduleinfo, $elname) and $moduleinfo->$elname) {
                // So we have a request for new outcome grade item?
                if ($items) {
                    $outcomeexists = false;
                    foreach($items as $item) {
                        if ($item->outcomeid == $outcome->id) {
                            $outcomeexists = true;
                            break;
                        }
                    }
                    if ($outcomeexists) {
                        continue;
                    }
                }

                $max_itemnumber++;

                $outcome_item = new grade_item();
                $outcome_item->courseid     = $course->id;
                $outcome_item->itemtype     = 'mod';
                $outcome_item->itemmodule   = $moduleinfo->modulename;
                $outcome_item->iteminstance = $moduleinfo->instance;
                $outcome_item->itemnumber   = $max_itemnumber;
                $outcome_item->itemname     = $outcome->fullname;
                $outcome_item->outcomeid    = $outcome->id;
                $outcome_item->gradetype    = GRADE_TYPE_SCALE;
                $outcome_item->scaleid      = $outcome->scaleid;
                $outcome_item->insert();

                // Move the new outcome into correct category and fix sortorder if needed.
                if ($grade_item) {
                    $outcome_item->set_parent($grade_item->categoryid);
                    $outcome_item->move_after_sortorder($grade_item->sortorder);

                } else if (isset($moduleinfo->gradecat)) {
                    $outcome_item->set_parent($moduleinfo->gradecat);
                }
            }
        }
    }

    if (plugin_supports('mod', $moduleinfo->modulename, FEATURE_ADVANCED_GRADING, false)
            and has_capability('moodle/grade:managegradingforms', $modcontext)) {
        require_once($CFG->dirroot.'/grade/grading/lib.php');
        $gradingman = get_grading_manager($modcontext, 'mod_'.$moduleinfo->modulename);
        $showgradingmanagement = false;
        foreach ($gradingman->get_available_areas() as $areaname => $aretitle) {
            $formfield = 'advancedgradingmethod_'.$areaname;
            if (isset($moduleinfo->{$formfield})) {
                $gradingman->set_area($areaname);
                $methodchanged = $gradingman->set_active_method($moduleinfo->{$formfield});
                if (empty($moduleinfo->{$formfield})) {
                    // Going back to the simple direct grading is not a reason to open the management screen.
                    $methodchanged = false;
                }
                $showgradingmanagement = $showgradingmanagement || $methodchanged;
            }
        }
        // Update grading management information.
        $moduleinfo->gradingman = $gradingman;
        $moduleinfo->showgradingmanagement = $showgradingmanagement;
    }

    rebuild_course_cache($course->id, true);

    if ($hasgrades) {
        // Allow async regrading.
        if (\core_course\local\grade_helper::use_async_course_regrade($course->id)) {
            core\task\grade_regrade_final_grades_task::enqueue($course->id);
        } else {
            grade_regrade_final_grades($course->id);
        }
    }

    require_once($CFG->libdir.'/plagiarismlib.php');
    plagiarism_save_form_elements($moduleinfo);

    // Allow plugins to extend the course module form.
    $moduleinfo = plugin_extend_coursemodule_edit_post_actions($moduleinfo, $course);

    return $moduleinfo;
}


/**
 * Set module info default values for the unset module attributs.
 *
 * @param object $moduleinfo the current known data of the module
 * @return object the completed module info
 *
 * @deprecated since Totara 13.0
 */
function set_moduleinfo_defaults($moduleinfo) {
//    debugging(
//        "The function 'set_moduleinfo_defaults' has been deprecated, please use " .
//        "\container_course\module\course_module_helper::set_moduleinfo_defaults instead",
//        DEBUG_DEVELOPER
//    );

    $updated = \container_course\module\course_module_helper::set_moduleinfo_defaults($moduleinfo);

    // For backward compatibility. We need to update the source of truth.
    $properties = get_object_vars($updated);

    foreach ($properties as $property => $value) {
        if (property_exists($moduleinfo, $property)) {
            $moduleinfo->{$property} = $value;
        }
    }

    return $moduleinfo;
}

/**
 * Check that the user can add a module. Also returns some information like the module, context and course section info.
 * The fucntion create the course section if it doesn't exist.
 *
 * @param object $course the course of the module
 * @param object $modulename the module name
 * @param object $section the section of the module
 * @return array list containing module, context, course section.
 * @throws moodle_exception if user is not allowed to perform the action or module is not allowed in this course
 */
function can_add_moduleinfo($course, $modulename, $section) {
    global $DB;

    $module = $DB->get_record('modules', array('name'=>$modulename), '*', MUST_EXIST);

    $context = context_course::instance($course->id);
    require_capability('moodle/course:manageactivities', $context);

    course_create_sections_if_missing($course, $section);
    $cw = get_fast_modinfo($course)->get_section_info($section);

    /** @var container_course\course $container_course */
    $container_course = core_container\factory::from_record($course);
    if (!container_course\course_helper::is_module_addable($module->name, $container_course)) {
        print_error('moduledisable');
    }

    return array($module, $context, $cw);
}

/**
 * Check if user is allowed to update module info and returns related item/data to the module.
 *
 * @param object $cm course module
 * @return array - list of course module, context, module, moduleinfo, and course section.
 * @throws moodle_exception if user is not allowed to perform the action
 */
function can_update_moduleinfo($cm) {
    global $DB;

    // Check the $USER has the right capability.
    $context = context_module::instance($cm->id);
    require_capability('moodle/course:manageactivities', $context);

    // Check module exists.
    $module = $DB->get_record('modules', array('id'=>$cm->module), '*', MUST_EXIST);

    // Check the moduleinfo exists.
    $data = $DB->get_record($module->name, array('id'=>$cm->instance), '*', MUST_EXIST);

    // Check the course section exists.
    $cw = $DB->get_record('course_sections', array('id'=>$cm->section), '*', MUST_EXIST);

    return array($cm, $context, $module, $data, $cw);
}


/**
 * Update the module info.
 * This function doesn't check the user capabilities. It updates the course module and the module instance.
 * Then execute common action to create/update module process (trigger event, rebuild cache, save plagiarism settings...).
 *
 * @param object $cm course module
 * @param object $moduleinfo module info
 * @param object $course course of the module
 * @param object $mform - the mform is required by some specific module in the function MODULE_update_instance(). This is due to a hack in this function.
 * @return array list of course module and module info.
 *
 * @deprecated since Totara 13.0
 */
function update_moduleinfo($cm, $moduleinfo, $course, $mform = null) {
//    debugging(
//        "The function 'update_moduleinfo' has been deprecated, please use " .
//        "\container_course\module\course_module::update instead",
//        DEBUG_DEVELOPER
//    );

    $container = \core_container\factory::from_record($course);
    $module = $container->get_module($cm->id);

    $module->update($moduleinfo, $mform);

    // For backward compatibility. We need to update the source of truth.
    // Update the source of truth for coursemodule
    $newcm = $module->get_cm_record();
    $properties = get_object_vars($newcm);
    foreach ($properties as $property => $value) {
        $cm->{$property} = $value;
    }

    // Update the source of truth for moduleinfo
    $properties = get_object_vars($module->to_record());
    foreach ($properties as $property => $value) {
        $moduleinfo->{$property} = $value;
    }

    return [$cm, $moduleinfo];
}

/**
 * Include once the module lib file.
 *
 * @param string $modulename module name of the lib to include
 * @throws moodle_exception if lib.php file for the module does not exist
 *
 * @deprecated since Totara 13.0
 */
function include_modulelib($modulename) {
//    debugging(
//        "The function 'include_modulelib' has been deprecated, please use " .
//        "\core_container\module\helper::include_modulelib instead",
//        DEBUG_DEVELOPER
//    );

    \core_container\module\helper::include_modulelib($modulename);
}

/**
 * Get module information data required for updating the module.
 *
 * @param  stdClass $cm     course module object
 * @param  stdClass $course course object
 * @return array required data for updating a module
 * @since  Moodle 3.2
 */
function get_moduleinfo_data($cm, $course) {
    global $CFG;

    [$cm, $context, $module, $data, $cw] = can_update_moduleinfo($cm);

    $data->coursemodule       = $cm->id;
    $data->section            = $cw->section;  // The section number itself - relative!!! (section column in course_sections)
    $data->visible            = $cm->visible; //??  $cw->visible ? $cm->visible : 0; // section hiding overrides
    $data->cmidnumber         = $cm->idnumber;          // The cm IDnumber
    $data->groupmode          = groups_get_activity_groupmode($cm); // locked later if forced
    $data->groupingid         = $cm->groupingid;
    $data->course             = $course->id;
    $data->module             = $module->id;
    $data->modulename         = $module->name;
    $data->instance           = $cm->instance;
    $data->completion         = $cm->completion;
    $data->completionview     = $cm->completionview;
    $data->completionexpected = $cm->completionexpected;
    $data->completionusegrade = is_null($cm->completiongradeitemnumber) ? 0 : 1;
    $data->showdescription    = $cm->showdescription;
    $data->tags               = core_tag_tag::get_item_tags_array('core', 'course_modules', $cm->id);
    if (!empty($CFG->enableavailability)) {
        $data->availabilityconditionsjson = $cm->availability;
    }

    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
        $currentintro = file_prepare_draft_area($draftid_editor, $context->id, 'mod_'.$data->modulename, 'intro', 0, array('subdirs'=>true), $data->intro);
        $data->introeditor = array('text'=>$currentintro, 'format'=>$data->introformat, 'itemid'=>$draftid_editor);
    }

    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
            and has_capability('moodle/grade:managegradingforms', $context)) {
        require_once($CFG->dirroot.'/grade/grading/lib.php');
        $gradingman = get_grading_manager($context, 'mod_'.$data->modulename);
        $data->_advancedgradingdata['methods'] = $gradingman->get_available_methods();
        $areas = $gradingman->get_available_areas();

        foreach ($areas as $areaname => $areatitle) {
            $gradingman->set_area($areaname);
            $method = $gradingman->get_active_method();
            $data->_advancedgradingdata['areas'][$areaname] = array(
                'title'  => $areatitle,
                'method' => $method,
            );
            $formfield = 'advancedgradingmethod_'.$areaname;
            $data->{$formfield} = $method;
        }
    }

    if ($items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$data->modulename,
                                             'iteminstance'=>$data->instance, 'courseid'=>$course->id))) {
        // Add existing outcomes.
        foreach ($items as $item) {
            if (!empty($item->outcomeid)) {
                $data->{'outcome_' . $item->outcomeid} = 1;
            } else if (isset($item->gradepass)) {
                $decimalpoints = $item->get_decimals();
                $data->gradepass = format_float($item->gradepass, $decimalpoints);
            }
        }

        // set category if present
        $gradecat = false;
        foreach ($items as $item) {
            if ($gradecat === false) {
                $gradecat = $item->categoryid;
                continue;
            }
            if ($gradecat != $item->categoryid) {
                //mixed categories
                $gradecat = false;
                break;
            }
        }
        if ($gradecat !== false) {
            // do not set if mixed categories present
            $data->gradecat = $gradecat;
        }
    }
    return array($cm, $context, $module, $data, $cw);
}

/**
 * Prepare the standard module information for a new module instance.
 *
 * @param  stdClass $course  course object
 * @param  string $modulename  module name
 * @param  int $section section number
 * @return array module information about other required data
 * @since  Moodle 3.2
 */
function prepare_new_moduleinfo_data($course, $modulename, $section) {
    global $CFG;

    [$module, $context, $cw] = can_add_moduleinfo($course, $modulename, $section);

    $cm = null;

    $data = new stdClass();
    $data->section          = $section;  // The section number itself - relative!!! (section column in course_sections)
    $data->visible          = $cw->visible;
    $data->course           = $course->id;
    $data->module           = $module->id;
    $data->modulename       = $module->name;
    $data->groupmode        = $course->groupmode;
    $data->groupingid       = $course->defaultgroupingid;
    $data->id               = '';
    $data->instance         = '';
    $data->coursemodule     = '';

    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
        file_prepare_draft_area($draftid_editor, null, null, null, null, array('subdirs'=>true));
        $data->introeditor = array('text' => '', 'itemid' => $draftid_editor);
    }

    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
            and has_capability('moodle/grade:managegradingforms', $context)) {
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        $data->_advancedgradingdata['methods'] = grading_manager::available_methods();
        $areas = grading_manager::available_areas('mod_'.$module->name);

        foreach ($areas as $areaname => $areatitle) {
            $data->_advancedgradingdata['areas'][$areaname] = array(
                'title'  => $areatitle,
                'method' => '',
            );
            $formfield = 'advancedgradingmethod_'.$areaname;
            $data->{$formfield} = '';
        }
    }

    return array($module, $context, $cw, $cm, $data);
}
