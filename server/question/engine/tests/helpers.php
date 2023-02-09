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
 * This file contains helper classes for testing the question engine.
 *
 * @package    moodlecore
 * @subpackage questionengine
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');


/**
 * Makes some protected methods of question_attempt public to facilitate testing.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_question_attempt extends question_attempt {
    public function add_step(question_attempt_step $step) {
        parent::add_step($step);
    }
    public function set_min_fraction($fraction) {
        $this->minfraction = $fraction;
    }
    public function set_max_fraction($fraction) {
        $this->maxfraction = $fraction;
    }
    public function set_behaviour(question_behaviour $behaviour) {
        $this->behaviour = $behaviour;
    }
}


/**
 * Test subclass to allow access to some protected data so that the correct
 * behaviour can be verified.
 *
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_question_engine_unit_of_work extends question_engine_unit_of_work {
    public function get_modified() {
        return $this->modified;
    }

    public function get_attempts_added() {
        return $this->attemptsadded;
    }

    public function get_attempts_modified() {
        return $this->attemptsmodified;
    }

    public function get_steps_added() {
        return $this->stepsadded;
    }

    public function get_steps_modified() {
        return $this->stepsmodified;
    }

    public function get_steps_deleted() {
        return $this->stepsdeleted;
    }

    public function get_metadata_added() {
        return $this->metadataadded;
    }

    public function get_metadata_modified() {
        return $this->metadatamodified;
    }
}


/**
 * Base class for question type test helpers.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_test_helper {
    /**
     * @return array of example question names that can be passed as the $which
     * argument of {@link test_question_maker::make_question} when $qtype is
     * this question type.
     */
    abstract public function get_test_questions();

    /**
     * Set up a form to create a question in $cat. This method also sets cat and contextid on $questiondata object.
     * @param object $cat the category
     * @param object $questiondata form initialisation requires question data.
     * @return moodleform
     */
    public static function get_question_editing_form($cat, $questiondata) {
        $catcontext = context::instance_by_id($cat->contextid, MUST_EXIST);
        $contexts = new question_edit_contexts($catcontext);
        $dataforformconstructor = new stdClass();
        $dataforformconstructor->qtype = $questiondata->qtype;
        $dataforformconstructor->contextid = $questiondata->contextid = $catcontext->id;
        $dataforformconstructor->category = $questiondata->category = $cat->id;
        $dataforformconstructor->formoptions = new stdClass();
        $dataforformconstructor->formoptions->canmove = true;
        $dataforformconstructor->formoptions->cansaveasnew = true;
        $dataforformconstructor->formoptions->canedit = true;
        $dataforformconstructor->formoptions->repeatelements = true;
        $qtype = question_bank::get_qtype($questiondata->qtype);
        return  $qtype->create_editing_form('question.php', $dataforformconstructor, $cat, $contexts, true);
    }
}


/**
 * This class creates questions of various types, which can then be used when
 * testing.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_question_maker {
    const STANDARD_OVERALL_CORRECT_FEEDBACK = 'Well done!';
    const STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK =
        'Parts, but only parts, of your response are correct.';
    const STANDARD_OVERALL_INCORRECT_FEEDBACK = 'That is not right at all.';

    /** @var array qtype => qtype test helper class. */
    protected static $testhelpers = array();

    /**
     * Just make a question_attempt at a question. Useful for unit tests that
     * need to pass a $qa to methods that call format_text. Probably not safe
     * to use for anything beyond that.
     * @param question_definition $question a question.
     * @param number $maxmark the max mark to set.
     * @return question_attempt the question attempt.
     */
    public static function get_a_qa($question, $maxmark = 3) {
        return new question_attempt($question, 13, null, $maxmark);
    }

    /**
     * Initialise the common fields of a question of any type.
     */
    public static function initialise_a_question($q) {
        global $USER;

        $q->id = 0;
        $q->category = 0;
        $q->parent = 0;
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;
        $q->length = 1;
        $q->stamp = make_unique_id_code();
        $q->version = make_unique_id_code();
        $q->hidden = 0;
        $q->timecreated = time();
        $q->timemodified = time();
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;
    }

    public static function initialise_question_data($qdata) {
        global $USER;

        $qdata->id = 0;
        $qdata->category = 0;
        $qdata->contextid = 0;
        $qdata->parent = 0;
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->penalty = 0.3333333;
        $qdata->length = 1;
        $qdata->stamp = make_unique_id_code();
        $qdata->version = make_unique_id_code();
        $qdata->hidden = 0;
        $qdata->timecreated = time();
        $qdata->timemodified = time();
        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->hints = array();
    }

    /**
     * Get the test helper class for a particular question type.
     * @param $qtype the question type name, e.g. 'multichoice'.
     * @return question_test_helper the test helper class.
     */
    public static function get_test_helper($qtype) {
        global $CFG;

        if (array_key_exists($qtype, self::$testhelpers)) {
            return self::$testhelpers[$qtype];
        }

        $file = core_component::get_plugin_directory('qtype', $qtype) . '/tests/helper.php';
        if (!is_readable($file)) {
            throw new coding_exception('Question type ' . $qtype .
                ' does not have test helper code.');
        }
        include_once($file);

        $class = 'qtype_' . $qtype . '_test_helper';
        if (!class_exists($class)) {
            throw new coding_exception('Class ' . $class . ' is not defined in ' . $file);
        }

        self::$testhelpers[$qtype] = new $class();
        return self::$testhelpers[$qtype];
    }

    /**
     * Call a method on a qtype_{$qtype}_test_helper class and return the result.
     *
     * @param string $methodtemplate e.g. 'make_{qtype}_question_{which}';
     * @param string $qtype the question type to get a test question for.
     * @param string $which one of the names returned by the get_test_questions
     *      method of the relevant qtype_{$qtype}_test_helper class.
     * @param unknown_type $which
     */
    protected static function call_question_helper_method($methodtemplate, $qtype, $which = null) {
        $helper = self::get_test_helper($qtype);

        $available = $helper->get_test_questions();

        if (is_null($which)) {
            $which = reset($available);
        } else if (!in_array($which, $available)) {
            throw new coding_exception('Example question ' . $which . ' of type ' .
                $qtype . ' does not exist.');
        }

        $method = str_replace(array('{qtype}', '{which}'),
            array($qtype,    $which), $methodtemplate);

        if (!method_exists($helper, $method)) {
            throw new coding_exception('Method ' . $method . ' does not exist on the ' .
                $qtype . ' question type test helper class.');
        }

        return $helper->$method();
    }

    /**
     * Question types can provide a number of test question defintions.
     * They do this by creating a qtype_{$qtype}_test_helper class that extends
     * question_test_helper. The get_test_questions method returns the list of
     * test questions available for this question type.
     *
     * @param string $qtype the question type to get a test question for.
     * @param string $which one of the names returned by the get_test_questions
     *      method of the relevant qtype_{$qtype}_test_helper class.
     * @return question_definition the requested question object.
     */
    public static function make_question($qtype, $which = null) {
        return self::call_question_helper_method('make_{qtype}_question_{which}',
            $qtype, $which);
    }

    /**
     * Like {@link make_question()} but returns the datastructure from
     * get_question_options instead of the question_definition object.
     *
     * @param string $qtype the question type to get a test question for.
     * @param string $which one of the names returned by the get_test_questions
     *      method of the relevant qtype_{$qtype}_test_helper class.
     * @return stdClass the requested question object.
     */
    public static function get_question_data($qtype, $which = null) {
        return self::call_question_helper_method('get_{qtype}_question_data_{which}',
            $qtype, $which);
    }

    /**
     * Like {@link make_question()} but returns the data what would be saved from
     * the question editing form instead of the question_definition object.
     *
     * @param string $qtype the question type to get a test question for.
     * @param string $which one of the names returned by the get_test_questions
     *      method of the relevant qtype_{$qtype}_test_helper class.
     * @return stdClass the requested question object.
     */
    public static function get_question_form_data($qtype, $which = null) {
        return self::call_question_helper_method('get_{qtype}_question_form_data_{which}',
            $qtype, $which);
    }

    /**
     * Makes a multichoice question with choices 'A', 'B' and 'C' shuffled. 'A'
     * is correct, defaultmark 1.
     * @return qtype_multichoice_single_question
     */
    public static function make_a_multichoice_single_question() {
        question_bank::load_question_definition_classes('multichoice');
        $mc = new qtype_multichoice_single_question();
        self::initialise_a_question($mc);
        $mc->name = 'Multi-choice question, single response';
        $mc->questiontext = 'The answer is A.';
        $mc->generalfeedback = 'You should have selected A.';
        $mc->qtype = question_bank::get_qtype('multichoice');

        $mc->shuffleanswers = 1;
        $mc->answernumbering = 'abc';

        $mc->answers = array(
            13 => new question_answer(13, 'A', 1, 'A is right', FORMAT_HTML),
            14 => new question_answer(14, 'B', -0.3333333, 'B is wrong', FORMAT_HTML),
            15 => new question_answer(15, 'C', -0.3333333, 'C is wrong', FORMAT_HTML),
        );

        return $mc;
    }

    /**
     * Makes a multichoice question with choices 'A', 'B', 'C' and 'D' shuffled.
     * 'A' and 'C' is correct, defaultmark 1.
     * @return qtype_multichoice_multi_question
     */
    public static function make_a_multichoice_multi_question() {
        question_bank::load_question_definition_classes('multichoice');
        $mc = new qtype_multichoice_multi_question();
        self::initialise_a_question($mc);
        $mc->name = 'Multi-choice question, multiple response';
        $mc->questiontext = 'The answer is A and C.';
        $mc->generalfeedback = 'You should have selected A and C.';
        $mc->qtype = question_bank::get_qtype('multichoice');

        $mc->shuffleanswers = 1;
        $mc->answernumbering = 'abc';

        self::set_standard_combined_feedback_fields($mc);

        $mc->answers = array(
            13 => new question_answer(13, 'A', 0.5, 'A is part of the right answer', FORMAT_HTML),
            14 => new question_answer(14, 'B', -1, 'B is wrong', FORMAT_HTML),
            15 => new question_answer(15, 'C', 0.5, 'C is part of the right answer', FORMAT_HTML),
            16 => new question_answer(16, 'D', -1, 'D is wrong', FORMAT_HTML),
        );

        return $mc;
    }

    /**
     * Makes a matching question to classify 'Dog', 'Frog', 'Toad' and 'Cat' as
     * 'Mammal', 'Amphibian' or 'Insect'.
     * defaultmark 1. Stems are shuffled by default.
     * @return qtype_match_question
     */
    public static function make_a_matching_question() {
        question_bank::load_question_definition_classes('match');
        $match = new qtype_match_question();
        self::initialise_a_question($match);
        $match->name = 'Matching question';
        $match->questiontext = 'Classify the animals.';
        $match->generalfeedback = 'Frogs and toads are amphibians, the others are mammals.';
        $match->qtype = question_bank::get_qtype('match');

        $match->shufflestems = 1;

        self::set_standard_combined_feedback_fields($match);

        // Using unset to get 1-based arrays.
        $match->stems = array('', 'Dog', 'Frog', 'Toad', 'Cat');
        $match->stemformat = array('', FORMAT_HTML, FORMAT_HTML, FORMAT_HTML, FORMAT_HTML);
        $match->choices = array('', 'Mammal', 'Amphibian', 'Insect');
        $match->right = array('', 1, 2, 2, 1);
        unset($match->stems[0]);
        unset($match->stemformat[0]);
        unset($match->choices[0]);
        unset($match->right[0]);

        return $match;
    }

    /**
     * Makes a truefalse question with correct ansewer true, defaultmark 1.
     * @return qtype_essay_question
     */
    public static function make_an_essay_question() {
        question_bank::load_question_definition_classes('essay');
        $essay = new qtype_essay_question();
        self::initialise_a_question($essay);
        $essay->name = 'Essay question';
        $essay->questiontext = 'Write an essay.';
        $essay->generalfeedback = 'I hope you wrote an interesting essay.';
        $essay->penalty = 0;
        $essay->qtype = question_bank::get_qtype('essay');

        $essay->responseformat = 'editor';
        $essay->responserequired = 1;
        $essay->responsefieldlines = 15;
        $essay->attachments = 0;
        $essay->attachmentsrequired = 0;
        $essay->responsetemplate = '';
        $essay->responsetemplateformat = FORMAT_MOODLE;
        $essay->graderinfo = '';
        $essay->graderinfoformat = FORMAT_MOODLE;

        return $essay;
    }

    /**
     * Add some standard overall feedback to a question. You need to use these
     * specific feedback strings for the corresponding contains_..._feedback
     * methods in {@link qbehaviour_walkthrough_test_base} to works.
     * @param question_definition $q the question to add the feedback to.
     */
    public static function set_standard_combined_feedback_fields($q) {
        $q->correctfeedback = self::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $q->correctfeedbackformat = FORMAT_HTML;
        $q->partiallycorrectfeedback = self::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK;
        $q->partiallycorrectfeedbackformat = FORMAT_HTML;
        $q->shownumcorrect = true;
        $q->incorrectfeedback = self::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $q->incorrectfeedbackformat = FORMAT_HTML;
    }

    /**
     * Add some standard overall feedback to a question's form data.
     */
    public static function set_standard_combined_feedback_form_data($form) {
        $form->correctfeedback = array('text' => self::STANDARD_OVERALL_CORRECT_FEEDBACK,
                                    'format' => FORMAT_HTML);
        $form->partiallycorrectfeedback = array('text' => self::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK,
                                             'format' => FORMAT_HTML);
        $form->shownumcorrect = true;
        $form->incorrectfeedback = array('text' => self::STANDARD_OVERALL_INCORRECT_FEEDBACK,
                                    'format' => FORMAT_HTML);
    }
}


/**
 * Helper for tests that need to simulate records loaded from the database.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class testing_db_record_builder {
    public static function build_db_records(array $table) {
        $columns = array_shift($table);
        $records = array();
        foreach ($table as $row) {
            if (count($row) != count($columns)) {
                throw new coding_exception("Row contains the wrong number of fields.");
            }
            $rec = new stdClass();
            foreach ($columns as $i => $name) {
                $rec->$name = $row[$i];
            }
            $records[] = $rec;
        }
        return $records;
    }
}

class question_contains_tag_with_contents {
    public $tag;
    public $content;
    public $message;

    public function __construct($tag, $content, $message = '') {
        $this->tag = $tag;
        $this->content = $content;
        $this->message = $message;
    }

}

class question_check_specified_fields_expectation {
    public $expect;
    public $message;

    function __construct($expected, $message = '') {
        $this->expect = $expected;
        $this->message = $message;
    }
}


class question_contains_select_expectation {
    public $name;
    public $choices;
    public $selected;
    public $enabled;
    public $message;

    public function __construct($name, $choices, $selected = null, $enabled = null, $message = '') {
        $this->name = $name;
        $this->choices = $choices;
        $this->selected = $selected;
        $this->enabled = $enabled;
        $this->message = $message;
    }
}


class question_does_not_contain_tag_with_attributes {
    public $tag;
    public $attributes;
    public $message;

    public function __construct($tag, $attributes, $message = '') {
        $this->tag = $tag;
        $this->attributes = $attributes;
        $this->message = $message;
    }
}


class question_contains_tag_with_attribute {
    public $tag;
    public $attribute;
    public $value;
    public $message;

    public function __construct($tag, $attribute, $value, $message = '') {
        $this->tag = $tag;
        $this->attribute = $attribute;
        $this->value = $value;
        $this->message = $message;
    }
}


class question_contains_tag_with_attributes {
    public $tag;
    public $expectedvalues = array();
    public $forbiddenvalues = array();
    public $message;

    public function __construct($tag, $expectedvalues, $forbiddenvalues=array(), $message = '') {
        $this->tag = $tag;
        $this->expectedvalues = $expectedvalues;
        $this->forbiddenvalues = $forbiddenvalues;
        $this->message = $message;
    }
}


class question_pattern_expectation {
    public $pattern;
    public $message;

    public function __construct($pattern, $message = '') {
        $this->pattern = $pattern;
        $this->message = $message;
    }
}


class question_no_pattern_expectation {
    public $pattern;
    public $message;

    public function __construct($pattern, $message = '') {
        $this->pattern = $pattern;
        $this->message = $message;
    }
}
