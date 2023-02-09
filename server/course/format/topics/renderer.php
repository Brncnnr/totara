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
 * Renderer for outputting the topics course format.
 *
 * @package format_topics
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/lib.php');
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_topics_renderer extends format_section_renderer_base {

    const BEM_PREFIX = 'tw-formatTopics';

    protected $collapsestates;

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        global $COURSE;

        parent::__construct($page, $target);

        // Since format_topics_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        user_preference_allow_ajax_update($this->topics_open_state_preference_name($COURSE->id), PARAM_RAW);
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'topics', 'role' => 'presentation'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        global $OUTPUT, $PAGE;

        $title = $this->render(course_get_format($course)->inplace_editable_render_section_name($section));

        if ($PAGE->user_is_editing() || !$course->collapsiblesections || $section->section == 0) {
            return $title;
        }

        $prefix = self::BEM_PREFIX;
        $id = "{$prefix}__topic-{$section->section}";
        $attributes = [
            'class' => "{$prefix}__collapse_link",
            'aria-expanded' => $this->section_is_expanded($section) ? 'true' : 'false',
            'aria-controls' => $id,
        ];

        $collapseicon = $OUTPUT->flex_icon('chevron-down', ['classes' => "{$prefix}__collapse_link_icon"]);
        $collapselink = html_writer::link('#'.$id, $collapseicon, $attributes);

        return $collapselink . $title;
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        // Totara: Keep isstealth here.
        $isstealth = $section->section > $course->numsections;
        $controls = array();
        if (!$isstealth && $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;

        $o = '';
        $prefix = self::BEM_PREFIX;
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        if ($section->section !== 0) {
            $sectionstyle .= " {$prefix}__topic";

            if (!$PAGE->user_is_editing()) {
                if ($course->collapsiblesections) {
                    $sectionstyle .= " {$prefix}__topic--collapsible";
                }

                if ($section->cssclasses) {
                    $sectionstyle .= " {$section->cssclasses}";
                }
            }
        }

        if (!$PAGE->user_is_editing() && $course->headercolor) {
            $sectionstyle .= " {$prefix}__topic--color";
        }

        $o .= html_writer::start_tag('li', [
            'id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle,
            'data-open' => $this->section_is_expanded($section) ? 'true' : null,
            'data-course-id' => $course->id,
            'data-section-id' => $section->id,
            'aria-label' => get_section_name($course, $section),
        ]);

        if (!$PAGE->user_is_editing() && $course->collapsiblesections && $course->collapsiblesectionscollapseall && $section->section == 1) {
            $modinfo = get_fast_modinfo($course);
            $collapse_all = html_writer::tag('a', get_string('collapseall'), ['href' => '#', 'class' => "{$prefix}__collapse_all"]);
            $expand_all = html_writer::tag('a', get_string('expandall'), ['href' => '#', 'class' => "{$prefix}__expand_all"]);
            $expanded_count = 0;
            $collapsed_count = 0;
            foreach ($modinfo->get_section_info_all() as $sec_index => $thissection) {
                if ($sec_index == 0 || $sec_index > $course->numsections) {
                    continue;
                }
                if ($this->section_is_expanded($thissection)) {
                    $expanded_count++;
                } else {
                    $collapsed_count++;
                }
            }
            $o .= html_writer::tag('div', $collapse_all . $expand_all, [
                'class' => "{$prefix}__all_toggles",
                'data-all-expanded' => $expanded_count == $course->numsections ? true : null,
                'data-all-collapsed' => $collapsed_count == $course->numsections ? true : null,
            ]);
        }

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        $o .= $this->section_header_header($section, $course, $onsectionpage);

        if (!$PAGE->user_is_editing()) {
            $classes = "{$prefix}__topic_content";
            $o .= html_writer::start_div($classes);
        }

        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        $o .= $this->format_summary_text($section);
        $o .= html_writer::end_tag('div');

        $context = context_course::instance($course->id);
        $o .= $this->section_availability_message($section,
                has_capability('moodle/course:viewhiddensections', $context));

        return $o;
    }

    /**
     * Generate section header control.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @return string HTML to output.
     */
    protected function section_header_header($section, $course, $onsectionpage) {
        global $PAGE;

        $prefix = self::BEM_PREFIX;

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = $hasnamenotsecpg || $hasnamesecpg ? '' : ' accesshide';

        $classes .= " {$prefix}__topic_header";
        $classes .= $this->section_header_header_classes($section, $course, $onsectionpage);
        $sectionname = html_writer::tag('span', $this->section_title($section, $course), array('data-movetext' => 'true'));
        if ($course->collapsiblesections && !$PAGE->user_is_editing() && $section->section != 0) {
            $classes .= " {$prefix}__collapse_handle";
        }

        $styles = '';
        if ($course->headercolor && !$PAGE->user_is_editing()) {
            if ($course->headerbgcolor) {
                $styles .= 'background-color:' . s($course->headerbgcolor) .';';
            }
            if ($course->headerfgcolor) {
                $styles .= 'color:' . s($course->headerfgcolor) .';';
            }
        }

        $attrs = [
            'class' => renderer_base::prepare_classes('sectionname' . $classes),
        ];
        if ($styles) {
            $attrs ['style'] = $styles;
        }
        return html_writer::tag('h3', $sectionname, $attrs);
    }

    /**
     * Get classes for section header control.
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     */
    protected function section_header_header_classes($section, $course, $onsectionpage) {
        return '';
    }

    protected function section_footer() {
        global $PAGE;

        $output = '';

        if (!$PAGE->user_is_editing()) {
            // End tw-formatTopics__topic_content... opened in section_header() above.
            $output .= html_writer::end_div();
        }

        $output .= parent::section_footer();

        return $output;
    }

    /**
     * Generate the html for a hidden section
     *
     * @param int $sectionno The section number in the course which is being displayed
     * @param int|stdClass $courseorid The course to get the section name for (object or just course id)
     * @return string HTML to output.
     */
    protected function section_hidden($sectionno, $courseorid = null) {
        global $PAGE;

        $course = course_get_format($courseorid)->get_course();
        $section = course_get_format($courseorid)->get_section($sectionno);

        $prefix = self::BEM_PREFIX;
        $sectionstyle = '';

        if ($section->section !== 0) {
            if (!$PAGE->user_is_editing()) {
                if ($course->collapsiblesections) {
                    $sectionstyle .= " {$prefix}__topic--collapsible";
                }

                if ($course->headercolor) {
                    $sectionstyle .= " {$prefix}__topic--color";
                }

                if ($section->cssclasses) {
                    $sectionstyle .= " {$section->cssclasses}";
                }
            }
        }


        if ($courseorid) {
            $sectionname = get_section_name($courseorid, $sectionno);
            $strnotavailable = get_string('notavailablecourse', '', $sectionname);
        } else {
            $strnotavailable = get_string('notavailable');
        }

        $o = '';
        $o.= html_writer::start_tag('li', array('id' => 'section-'.$sectionno, 'class' => 'section main clearfix hidden' . $sectionstyle));
        $o.= html_writer::tag('div', '', array('class' => 'left side'));
        $o.= html_writer::tag('div', '', array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));
        $o.= html_writer::tag('div', $strnotavailable);
        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('li');
        return $o;
    }

    /**
     * Get the current collapse state of the section.
     *
     * @param stdClass $section The course_section entry from DB
     * @param int $courseid
     * @return boolean
     */
    protected function section_is_expanded($section, $courseid = null) {
        global $COURSE;

        if ($courseid === null) {
            $courseid = $COURSE->id;
        }

        // The course wide section is not a topic.
        if ($section->section === 0) {
            return true;
        }

        $open_states = $this->get_open_states($courseid);

        // Is there already a user preference?
        if (isset($open_states->{$section->id})) {
            return $open_states->{$section->id} ? true : false;
        }

        $section_options = course_get_format($courseid)->get_format_options($section);
        $collapseddefault = isset($section_options['collapseddefault'])
            ? $section_options['collapseddefault']
            : format_topics::COLLAPSE_STATE_AUTO;

        if ($collapseddefault == format_topics::COLLAPSE_STATE_COLLAPSED) {
            return false;
        }
        if ($collapseddefault == format_topics::COLLAPSE_STATE_EXPANDED) {
            return true;
        }

        // Defaults - first topic expanded rest collapsed.
        return $section->section === 1;
    }

    /**
     * Generate user preference name
     *
     * @param int $courseid
     * @return string
     */
    protected function topics_open_state_preference_name($courseid) {
        return "format_topics/topics_open_state.{$courseid}";
    }

    /**
     * Get collapsestates as stdClass prepopulating not yet set.
     *
     * @param int $courseid
     * @return stdClass
     */
    protected function get_open_states($courseid) {
        // Static cache.
        if ($this->collapsestates !== null) {
            return $this->collapsestates;
        }

        // Totara: Handle null from get_user_preferences throwing a deprecation if passed into json_decode
        $user_preferences = get_user_preferences($this->topics_open_state_preference_name($courseid));
        $this->collapsestates = $user_preferences === null ? null : json_decode($user_preferences);

        // Deal with an unset preference.
        if ($this->collapsestates === null) {
            $this->collapsestates = new stdClass();
        }

        return $this->collapsestates;
    }
}
