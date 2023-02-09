<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */
require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');

/**
 * Constants for defining JS to load
 */
define('TOTARA_JS_DIALOG',         1);
define('TOTARA_JS_TREEVIEW',       2);
define('TOTARA_JS_DATEPICKER',     3);
define('TOTARA_JS_ICON_PREVIEW',   5);
define('TOTARA_JS_UI',             6);

/**
 * Load appropriate JS and CSS files for lightbox
 *
 * @param $options array Array of option constants
 */
function local_js($options = array()) {
    global $CFG, $PAGE;

    // Include required javascript libraries
    // jQuery component and UI bundle found here: http://jqueryui.com/download
    // Core, Widget, Position, Dialog, Tabs, Datepicker, Effects Core, Effects "Fade"

    // jQuery is loaded on each page since 2.9.0.
    if (count($options) === 0) {
        debugging('Totara loads jQuery on every page since 2.9.0', DEBUG_DEVELOPER);
        return;
    }

    $directory = $CFG->cachejs ? 'build' : 'src';
    $min = $CFG->cachejs ? '.min' : '';

    // If UI
    if (in_array(TOTARA_JS_UI, $options)) {

        $PAGE->requires->js('/totara/core/js/lib/jquery-ui-1.10.4.custom.min.js');

    }

    // If dialog
    if (in_array(TOTARA_JS_DIALOG, $options)) {

        $PAGE->requires->js('/totara/core/js/lib/jquery-ui-1.10.4.custom.min.js');

        // Load required strings into the JS global namespace in the form
        // M.str.COMPONENT.IDENTIFIER, eg; M.str.totara_core['save']. Can also
        // be accessed with M.util.get_string(IDENTIFIER, COMPONENT), use third
        // arg for a single {$a} replacement. See /lib/outputrequirementslib.php
        // for detail and limitations.
        $PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
        $PAGE->requires->strings_for_js(array('ok', 'cancel'), 'moodle');

        // Include the totara_dialog JS module. Args supplied to the module's
        // init method must be a php array (or null if none), the first index
        // being a JSON formatted string of args, which are parsed into a config
        // object stored in the module, eg; array('args'=>'{"id":' .$id. '}')
        // which is then available via M.totara_dialog.config.id once the module
        // has loaded. Further args can be supplied to the init method but are
        // not JSON parsed, but are still available via the usual 'arguments'
        // object of the init method.
        $jsmodule = array(
                'name' => 'totara_dialog',
                'fullpath' => '/totara/core/js/lib/' . $directory . '/totara_dialog' . $min . '.js',
                'requires' => array('json'));
        $PAGE->requires->js_init_call('M.totara_dialog.init', null, false, $jsmodule);
    }

    // If treeview enabled
    if (in_array(TOTARA_JS_TREEVIEW, $options)) {

        $PAGE->requires->js('/totara/core/js/lib/' . $directory . '/jquery.treeview' . $min . '.js');

    }

    // If datepicker enabled
    if (in_array(TOTARA_JS_DATEPICKER, $options)) {

        $PAGE->requires->js('/totara/core/js/lib/jquery-ui-1.10.4.custom.min.js');

        $PAGE->requires->strings_for_js(array('datepickerlongyeardisplayformat', 'datepickerlongyearplaceholder', 'datepickerlongyearregexjs'), 'totara_core');
        $PAGE->requires->string_for_js('thisdirection', 'langconfig');

        $lang = current_language();

        // include datepicker localization file if present for current language
        $file = "/totara/core/js/lib/i18n/jquery.ui.datepicker-{$lang}.js";
        if (is_readable($CFG->dirroot . $file)) {
            $PAGE->requires->js($file);
        }


    }

    // If Icon preview is enabled
    if (in_array(TOTARA_JS_ICON_PREVIEW, $options)) {

        $PAGE->requires->js('/totara/core/js/icon.preview.js');

    }
}

/**
 * Adds JS datepicker setup call to page
 *
 * @param string $selector A JQuery Selector string referencing the element to add
 *                         the picker to
 * @param string $dateformat (optional) provide if format should not be standard dd/mm/yy
 */
function build_datepicker_js($selector, $dateformat=null) {
    global $PAGE;

    $PAGE->requires->strings_for_js(array('datepickerlongyeardisplayformat', 'datepickerlongyearplaceholder', 'datepickerlongyearregexjs'), 'totara_core');

    if (empty($dateformat)) {
        $dateformat = get_string('datepickerlongyeardisplayformat', 'totara_core');
    }
    $button_img = array('t/calendar', 'totara_core');
    $args = array($selector, $dateformat, $button_img);
    $PAGE->requires->js_init_call('M.totara_core.build_datepicker', $args);
}

/**
 * Return markup for a branch of a hierarchy based treeview
 *
 * @param   $elements       array       Single level array of elements
 * @param   $error_string   string      String to display if no elements supplied
 * @param   $hierarchy      object      The hierarchy object (optional)
 * @param   $disabledlist   array       Array of IDs of elements that should be disabled
 * @uses    $CFG
 * @return  $html
 */
function build_treeview($elements, $error_string, $hierarchy = null, $disabledlist = array()) {

    global $CFG, $OUTPUT;
    // maximum number of items to load (at any one level)
    // before giving up and suggesting search instead.
    $maxitems = TOTARA_DIALOG_MAXITEMS;

    $html = '';

    $buttons = array('addbutton' => 'add',
                     'deletebutton' => 'delete');

    if (is_array($elements) && !empty($elements)) {

        if(count($elements) > $maxitems) {
            $html .= '<li class="last"><span class="empty dialog-nobind">';
            $html .= get_string('error:morethanxitemsatthislevel', 'totara_core', $maxitems);
            $html .= ' <a href="#search-tab" onclick="$(\'#tabs\').tabs(\'select\', 1);return false;">';
            $html .= get_string('trysearchinginstead', 'totara_core');
            $html .= '</a>';
            $html .= '</span></li>'.PHP_EOL;
            return $html;
        }

        // Get parents array
        if ($hierarchy) {
            $parents = $hierarchy->get_all_parents();
        } else {
            $parents = array();
        }

        $total = count($elements);
        $count = 0;

        // Loop through elements
        foreach ($elements as $element) {
            ++$count;

            // Initialise class vars
            $li_class = '';
            $div_class = '';
            $span_class = '';

            // If last element
            if ($count == $total) {
                $li_class .= ' last';
            }

            // If element has children
            if (array_key_exists($element->id, $parents)) {
                $li_class .= ' expandable closed';
                $div_class .= ' hitarea closed-hitarea expandable-hitarea';
                $span_class .= ' folder';

                if ($count == $total) {
                    $li_class .= ' lastExpandable';
                    $div_class .= ' lastExpandable-hitarea';
                }
            }

            $addbutton_html = '<img src="'.$OUTPUT->image_url('t/'.$buttons['addbutton']).'" class="addbutton" />';

            // Make disabled elements non-draggable and greyed out
            if (array_key_exists($element->id, $disabledlist)){
                $span_class .= ' unclickable';
                $addbutton_html = '';
            }

            $html .= '<li class="'.trim($li_class).'" id="item_list_'.$element->id.'">';
            $html .= '<div class="'.trim($div_class).'"></div>';
            $html .= '<span id="item_'.$element->id.'" class="'.trim($span_class).'">';
            // format_string() really slow here...
            $html .= '<table><tr>';
            $html .= '<td class="list-item-name">'.format_string($element->fullname).'</td>';
            $html .= '<td class="list-item-action">'.$addbutton_html.'</td>';
            $html .= '</tr></table>';
            $html .= '</span>';

            if ($div_class !== '') {
                $html .= '<ul style="display: none;"></ul>';
            }
            $html .= '</li>'.PHP_EOL;
        }
    }
    else {
        $html .= '<li class="last"><span class="empty">';
        $html .= $error_string;
        $html .= '</span></li>'.PHP_EOL;
    }

    // Add hidden button images that can later be used/cloned by js TODO: add tooltip get_string
    foreach ($buttons as $classname => $pic) {
        $html .= '<img id="'.$classname.'_ex" src="'.$OUTPUT->image_url('t/'.$pic).'"
            class="'.$classname.'" style="display: none;" />';
    }

    return $html;
}

/**
 * Return markup for category treeview skeleton
 *
 * @param   $list           array       Array of full cat path names
 * @param   $parents        array       Array of category parents
 * @param   $load_string    string      String to display as a placeholder for unloaded branches
 * @uses    $CFG
 * @return  $html
 */
function build_category_treeview($list, $parents, $load_string) {

    global $CFG, $OUTPUT;

    $buttons = array('addbutton' => 'add',
                     'deletebutton' => 'delete');

    $html = '';

    if (is_array($list) && !empty($list)) {

        $len = count($list);
        $i = 0;
        $parent = array();

        // Add empty category to end of array to trigger
        // closing nested lists
        $list[] = null;

        foreach ($list as $id => $category) {
            ++$i;

            // If an actual category
            if ($category !== null) {
                if (!isset($parents[$id])) {
                    $this_parent = array();
                } else {
                    $this_parents = array_reverse($parents[$id]);
                    $this_parent = $parents[$id];
                }
            // If placeholder category at end
            } else {
                $this_parent = array();
            }

            if ($this_parent == $parent) {
                if ($i > 1) {
                    $html .= '<li class="last loading"><div></div><span>'.$load_string.'</span></li></ul></li>'.PHP_EOL;
                }
            } else {
                // If there are less parents now
                $diff = count($parent) - count($this_parent);

                if ($diff) {
                    $html .= str_repeat(
                        '<li class="last loading"><div></div><span>'.$load_string.'</span></li></ul>'.PHP_EOL,
                         $diff + 1
                    );
                }

                $parent = $this_parent;
            }

            if ($category !== null) {
                // Grab category name
                $rpos = strrpos($category, ' / ');
                if ($rpos) {
                    $category = substr($category, $rpos + 3);
                }

                $li_class = 'expandable closed';
                $div_class = 'hitarea closed-hitarea expandable-hitarea';

                if ($i == $len) {
                    $li_class .= ' lastExpandable';
                    $div_class .= ' lastExpandable-hitarea';
                }

                $html .= '<li class="'.$li_class.'" id="item_list_'.$id.'"><div class="'.$div_class.'"></div>';
                $html .= '<span class="folder">'.$category.'</span><ul style="display: none;">'.PHP_EOL;
            }
        }

        // Add hidden button images that can later be used/cloned by js TODO: add tooltip get_string
        foreach ($buttons as $classname => $pic) {
            $html .= '<img id="'.$classname.'_ex" src="'.$OUTPUT->image_url('t/'.$pic).'"
                class="'.$classname.'" style="display: none;" />';
        }
    }

    return $html;
}

/**
 * Return markup for 'Currently selected' info in a dialog
 * @param string $label the label
 * @param string $title the unique title of the dialog
 * @return string
 */
function dialog_display_currently_selected($label, $title='') {

    $outerid = "treeview_currently_selected_span_{$title}";
    $innerid = "treeview_selected_text_{$title}";
    $valid = "treeview_selected_val_{$title}";

    $html = ' ' . html_writer::start_tag('span', array('id' => $outerid, 'style' => 'display: none;'));
    $html .= '(' . html_writer::tag('label', $label, array('for' => $innerid)) . ':&nbsp;';
    $html .= html_writer::tag('em', html_writer::tag('span', '', array('id' => $innerid)));
    $html .= ')' . html_writer::end_tag('span');

    // Also add a hidden field that can hold the currently selected value
    $attr = array('type' => 'hidden', 'id' => $valid, 'name' => $valid, 'value' => '');
    $html .= html_writer::empty_tag('input', $attr);

    return $html;
}
