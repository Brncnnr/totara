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

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/question_testcase.php');

/**
 * Helper base class for tests that walk a question through a sequents of
 * interactions under the control of a particular behaviour.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qbehaviour_walkthrough_test_base extends question_testcase {
    /** @var question_display_options */
    protected $displayoptions;
    /** @var question_usage_by_activity */
    protected $quba;
    /** @var integer */

    protected $slot;
    /**
     * @var string after {@link render()} has been called, this contains the
     * display of the question in its current state.
     */
    protected $currentoutput = '';

    protected function setUp(): void {
        parent::setUp();

        $this->displayoptions = new question_display_options();
        $this->quba = question_engine::make_questions_usage_by_activity('unit_test',
            context_system::instance());
    }

    protected function tearDown(): void {
        $this->displayoptions = null;
        $this->quba = null;
        parent::tearDown();
    }

    protected function start_attempt_at_question($question, $preferredbehaviour,
                                                 $maxmark = null, $variant = 1) {
        $this->quba->set_preferred_behaviour($preferredbehaviour);
        $this->slot = $this->quba->add_question($question, $maxmark);
        $this->quba->start_question($this->slot, $variant);
    }

    /**
     * Convert an array of data destined for one question to the equivalent POST data.
     * @param array $data the data for the quetsion.
     * @return array the complete post data.
     */
    protected function response_data_to_post($data) {
        $prefix = $this->quba->get_field_prefix($this->slot);
        $fulldata = array(
            'slots' => $this->slot,
            $prefix . ':sequencecheck' => $this->get_question_attempt()->get_sequence_check_count(),
        );
        foreach ($data as $name => $value) {
            $fulldata[$prefix . $name] = $value;
        }
        return $fulldata;
    }

    protected function process_submission($data) {
        // Backwards compatibility.
        reset($data);
        if (count($data) == 1 && key($data) === '-finish') {
            $this->finish();
        }

        $this->quba->process_all_actions(time(), $this->response_data_to_post($data));
    }

    protected function process_autosave($data) {
        $this->quba->process_all_autosaves(null, $this->response_data_to_post($data));
    }

    protected function finish() {
        $this->quba->finish_all_questions();
    }

    protected function manual_grade($comment, $mark, $commentformat = null) {
        $this->quba->manual_grade($this->slot, $comment, $mark, $commentformat);
    }

    protected function save_quba(moodle_database $db = null) {
        question_engine::save_questions_usage_by_activity($this->quba, $db);
    }

    protected function load_quba(moodle_database $db = null) {
        $this->quba = question_engine::load_questions_usage_by_activity($this->quba->get_id(), $db);
    }

    protected function delete_quba() {
        question_engine::delete_questions_usage_by_activity($this->quba->get_id());
        $this->quba = null;
    }

    protected function check_current_state($state) {
        $this->assertEquals($state, $this->quba->get_question_state($this->slot),
            'Questions is in the wrong state.');
    }

    protected function check_current_mark($mark) {
        if (is_null($mark)) {
            $this->assertNull($this->quba->get_question_mark($this->slot));
        } else {
            if ($mark == 0) {
                // PHP will think a null mark and a mark of 0 are equal,
                // so explicity check not null in this case.
                $this->assertNotNull($this->quba->get_question_mark($this->slot));
            }
            $this->assertEqualsWithDelta($mark, $this->quba->get_question_mark($this->slot),
                0.000001, 'Expected mark and actual mark differ.');
        }
    }

    /**
     * Generate the HTML rendering of the question in its current state in
     * $this->currentoutput so that it can be verified.
     */
    protected function render() {
        $this->currentoutput = $this->quba->render_question($this->slot, $this->displayoptions);
    }

    protected function check_output_contains_text_input($name, $value = null, $enabled = true) {
        $attributes = array(
            'type' => 'text',
            'name' => $this->quba->get_field_prefix($this->slot) . $name,
        );
        if (!is_null($value)) {
            $attributes['value'] = $value;
        }
        if (!$enabled) {
            $attributes['readonly'] = 'readonly';
        }
        $matcher = $this->get_tag_matcher('input', $attributes);
        $this->assertTag($matcher, $this->currentoutput,
            'Looking for an input with attributes ' . html_writer::attributes($attributes) . ' in ' . $this->currentoutput);

        if ($enabled) {
            $matcher['attributes']['readonly'] = 'readonly';
            $this->assertNotTag($matcher, $this->currentoutput,
                'input with attributes ' . html_writer::attributes($attributes) .
                ' should not be read-only in ' . $this->currentoutput);
        }
    }

    protected function check_output_contains_text_input_with_class($name, $class = null) {
        $attributes = array(
            'type' => 'text',
            'name' => $this->quba->get_field_prefix($this->slot) . $name,
        );
        if (!is_null($class)) {
            $attributes['class'] = 'regexp:/\b' . $class . '\b/';
        }

        $matcher = $this->get_tag_matcher('input', $attributes);
        $this->assertTag($matcher, $this->currentoutput,
            'Looking for an input with attributes ' . html_writer::attributes($attributes) . ' in ' . $this->currentoutput);
    }

    protected function check_output_does_not_contain_text_input_with_class($name, $class = null) {
        $attributes = array(
            'type' => 'text',
            'name' => $this->quba->get_field_prefix($this->slot) . $name,
        );
        if (!is_null($class)) {
            $attributes['class'] = 'regexp:/\b' . $class . '\b/';
        }

        $matcher = $this->get_tag_matcher('input', $attributes);
        $this->assertNotTag($matcher, $this->currentoutput,
            'Unexpected input with attributes ' . html_writer::attributes($attributes) . ' found in ' . $this->currentoutput);
    }

    protected function check_output_contains_hidden_input($name, $value) {
        $attributes = array(
            'type' => 'hidden',
            'name' => $this->quba->get_field_prefix($this->slot) . $name,
            'value' => $value,
        );
        $this->assertTag($this->get_tag_matcher('input', $attributes), $this->currentoutput,
            'Looking for a hidden input with attributes ' . html_writer::attributes($attributes) . ' in ' . $this->currentoutput);
    }

    protected function check_output_contains($string) {
        $this->render();
        $this->assertStringContainsString($string, $this->currentoutput,
            'Expected string ' . $string . ' not found in ' . $this->currentoutput);
    }

    protected function check_output_does_not_contain($string) {
        $this->render();
        $this->assertStringNotContainsString($string, $this->currentoutput,
            'String ' . $string . ' unexpectedly found in ' . $this->currentoutput);
    }

    protected function check_output_contains_lang_string($identifier, $component = '', $a = null) {
        $this->check_output_contains(get_string($identifier, $component, $a));
    }

    protected function get_tag_matcher($tag, $attributes) {
        return array(
            'tag' => $tag,
            'attributes' => $attributes,
        );
    }

    /**
     * @param $condition one or more Expectations. (users varargs).
     */
    protected function check_current_output() {
        $html = $this->quba->render_question($this->slot, $this->displayoptions);
        foreach (func_get_args() as $condition) {
            $this->assert($condition, $html);
        }
    }

    protected function get_question_attempt() {
        return $this->quba->get_question_attempt($this->slot);
    }

    protected function get_step_count() {
        return $this->get_question_attempt()->get_num_steps();
    }

    protected function check_step_count($expectednumsteps) {
        $this->assertEquals($expectednumsteps, $this->get_step_count());
    }

    protected function get_step($stepnum) {
        return $this->get_question_attempt()->get_step($stepnum);
    }

    protected function get_contains_question_text_expectation($question) {
        return new question_pattern_expectation('/' . preg_quote($question->questiontext, '/') . '/');
    }

    protected function get_contains_general_feedback_expectation($question) {
        return new question_pattern_expectation('/' . preg_quote($question->generalfeedback, '/') . '/');
    }

    protected function get_does_not_contain_correctness_expectation() {
        return new question_no_pattern_expectation('/class=\"correctness/');
    }

    protected function get_contains_correct_expectation() {
        return new question_pattern_expectation('/' . preg_quote(get_string('correct', 'question'), '/') . '/');
    }

    protected function get_contains_partcorrect_expectation() {
        return new question_pattern_expectation('/' .
            preg_quote(get_string('partiallycorrect', 'question'), '/') . '/');
    }

    protected function get_contains_incorrect_expectation() {
        return new question_pattern_expectation('/' . preg_quote(get_string('incorrect', 'question'), '/') . '/');
    }

    protected function get_contains_standard_correct_combined_feedback_expectation() {
        return new question_pattern_expectation('/' .
            preg_quote(test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK, '/') . '/');
    }

    protected function get_contains_standard_partiallycorrect_combined_feedback_expectation() {
        return new question_pattern_expectation('/' .
            preg_quote(test_question_maker::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK, '/') . '/');
    }

    protected function get_contains_standard_incorrect_combined_feedback_expectation() {
        return new question_pattern_expectation('/' .
            preg_quote(test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK, '/') . '/');
    }

    protected function get_does_not_contain_feedback_expectation() {
        return new question_no_pattern_expectation('/class="feedback"/');
    }

    protected function get_does_not_contain_num_parts_correct() {
        return new question_no_pattern_expectation('/class="numpartscorrect"/');
    }

    protected function get_contains_num_parts_correct($num) {
        $a = new stdClass();
        $a->num = $num;
        return new question_pattern_expectation('/<div class="numpartscorrect">' .
            preg_quote(get_string('yougotnright', 'question', $a), '/') . '/');
    }

    protected function get_does_not_contain_specific_feedback_expectation() {
        return new question_no_pattern_expectation('/class="specificfeedback"/');
    }

    protected function get_contains_validation_error_expectation() {
        return new question_contains_tag_with_attribute('div', 'class', 'validationerror');
    }

    protected function get_does_not_contain_validation_error_expectation() {
        return new question_no_pattern_expectation('/class="validationerror"/');
    }

    protected function get_contains_mark_summary($mark) {
        $a = new stdClass();
        $a->mark = format_float($mark, $this->displayoptions->markdp);
        $a->max = format_float($this->quba->get_question_max_mark($this->slot),
            $this->displayoptions->markdp);
        return new question_pattern_expectation('/' .
            preg_quote(get_string('markoutofmax', 'question', $a), '/') . '/');
    }

    protected function get_contains_marked_out_of_summary() {
        $max = format_float($this->quba->get_question_max_mark($this->slot),
            $this->displayoptions->markdp);
        return new question_pattern_expectation('/' .
            preg_quote(get_string('markedoutofmax', 'question', $max), '/') . '/');
    }

    protected function get_does_not_contain_mark_summary() {
        return new question_no_pattern_expectation('/<div class="grade">/');
    }

    protected function get_contains_checkbox_expectation($baseattr, $enabled, $checked) {
        $expectedattributes = $baseattr;
        $forbiddenattributes = array();
        $expectedattributes['type'] = 'checkbox';
        if ($enabled === true) {
            $forbiddenattributes['disabled'] = 'disabled';
        } else if ($enabled === false) {
            $expectedattributes['disabled'] = 'disabled';
        }
        if ($checked === true) {
            $expectedattributes['checked'] = 'checked';
        } else if ($checked === false) {
            $forbiddenattributes['checked'] = 'checked';
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, $forbiddenattributes);
    }

    protected function get_contains_mc_checkbox_expectation($index, $enabled = null,
                                                            $checked = null) {
        return $this->get_contains_checkbox_expectation(array(
            'name' => $this->quba->get_field_prefix($this->slot) . $index,
            'value' => 1,
        ), $enabled, $checked);
    }

    protected function get_contains_radio_expectation($baseattr, $enabled, $checked) {
        $expectedattributes = $baseattr;
        $forbiddenattributes = array();
        $expectedattributes['type'] = 'radio';
        if ($enabled === true) {
            $forbiddenattributes['disabled'] = 'disabled';
        } else if ($enabled === false) {
            $expectedattributes['disabled'] = 'disabled';
        }
        if ($checked === true) {
            $expectedattributes['checked'] = 'checked';
        } else if ($checked === false) {
            $forbiddenattributes['checked'] = 'checked';
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, $forbiddenattributes);
    }

    protected function get_contains_mc_radio_expectation($index, $enabled = null, $checked = null) {
        return $this->get_contains_radio_expectation(array(
            'name' => $this->quba->get_field_prefix($this->slot) . 'answer',
            'value' => $index,
        ), $enabled, $checked);
    }

    protected function get_contains_hidden_expectation($name, $value = null) {
        $expectedattributes = array('type' => 'hidden', 'name' => s($name));
        if (!is_null($value)) {
            $expectedattributes['value'] = s($value);
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes);
    }

    protected function get_does_not_contain_hidden_expectation($name, $value = null) {
        $expectedattributes = array('type' => 'hidden', 'name' => s($name));
        if (!is_null($value)) {
            $expectedattributes['value'] = s($value);
        }
        return new question_does_not_contain_tag_with_attributes('input', $expectedattributes);
    }

    protected function get_contains_tf_true_radio_expectation($enabled = null, $checked = null) {
        return $this->get_contains_radio_expectation(array(
            'name' => $this->quba->get_field_prefix($this->slot) . 'answer',
            'value' => 1,
        ), $enabled, $checked);
    }

    protected function get_contains_tf_false_radio_expectation($enabled = null, $checked = null) {
        return $this->get_contains_radio_expectation(array(
            'name' => $this->quba->get_field_prefix($this->slot) . 'answer',
            'value' => 0,
        ), $enabled, $checked);
    }

    protected function get_contains_cbm_radio_expectation($certainty, $enabled = null,
                                                          $checked = null) {
        return $this->get_contains_radio_expectation(array(
            'name' => $this->quba->get_field_prefix($this->slot) . '-certainty',
            'value' => $certainty,
        ), $enabled, $checked);
    }

    protected function get_contains_button_expectation($name, $value = null, $enabled = null) {
        $expectedattributes = array(
            'type' => 'submit',
            'name' => $name,
        );
        $forbiddenattributes = array();
        if (!is_null($value)) {
            $expectedattributes['value'] = $value;
        }
        if ($enabled === true) {
            $forbiddenattributes['disabled'] = 'disabled';
        } else if ($enabled === false) {
            $expectedattributes['disabled'] = 'disabled';
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, $forbiddenattributes);
    }

    /**
     * Returns an epectation that a string contains the HTML of a button with
     * name {question-attempt prefix}-submit, and eiter enabled or not.
     * @param bool $enabled if not null, check the enabled/disabled state of the button. True = enabled.
     * @return question_contains_tag_with_attributes an expectation for use with check_current_output.
     */
    protected function get_contains_submit_button_expectation($enabled = null) {
        return $this->get_contains_button_expectation(
            $this->quba->get_field_prefix($this->slot) . '-submit', null, $enabled);
    }

    /**
     * Returns an epectation that a string does not contain the HTML of a button with
     * name {question-attempt prefix}-submit.
     * @return question_contains_tag_with_attributes an expectation for use with check_current_output.
     */
    protected function get_does_not_contain_submit_button_expectation() {
        return new question_no_pattern_expectation('/name="' .
            $this->quba->get_field_prefix($this->slot) . '-submit"/');
    }

    protected function get_tries_remaining_expectation($n) {
        return new question_pattern_expectation('/' .
            preg_quote(get_string('triesremaining', 'qbehaviour_interactive', $n), '/') . '/');
    }

    protected function get_invalid_answer_expectation() {
        return new question_pattern_expectation('/' .
            preg_quote(get_string('invalidanswer', 'question'), '/') . '/');
    }

    protected function get_contains_try_again_button_expectation($enabled = null) {
        $expectedattributes = array(
            'type' => 'submit',
            'name' => $this->quba->get_field_prefix($this->slot) . '-tryagain',
        );
        $forbiddenattributes = array();
        if ($enabled === true) {
            $forbiddenattributes['disabled'] = 'disabled';
        } else if ($enabled === false) {
            $expectedattributes['disabled'] = 'disabled';
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, $forbiddenattributes);
    }

    protected function get_does_not_contain_try_again_button_expectation() {
        return new question_no_pattern_expectation('/name="' .
            $this->quba->get_field_prefix($this->slot) . '-tryagain"/');
    }

    protected function get_contains_select_expectation($name, $choices,
                                                       $selected = null, $enabled = null) {
        $fullname = $this->quba->get_field_prefix($this->slot) . $name;
        return new question_contains_select_expectation($fullname, $choices, $selected, $enabled);
    }

    protected function get_mc_right_answer_index($mc) {
        $order = $mc->get_order($this->get_question_attempt());
        foreach ($order as $i => $ansid) {
            if ($mc->answers[$ansid]->fraction == 1) {
                return $i;
            }
        }
        $this->fail('This multiple choice question does not seem to have a right answer!');
    }

    protected function get_no_hint_visible_expectation() {
        return new question_no_pattern_expectation('/class="hint"/');
    }

    protected function get_contains_hint_expectation($hinttext) {
        // Does not currently verify hint text.
        return new question_contains_tag_with_attribute('div', 'class', 'hint');
    }
}
