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
 * Contains the helper class for the select missing words question type tests.
 *
 * @package    qtype_ddmatch
 * @copyright  2018 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the drag and rop matching question type.
 *
 * @copyright  2018 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('foursubq');
    }

    /**
     * Makes a matching question to classify 'Dog', 'Frog', 'Toad' and 'Cat' as
     * 'Mammal', 'Amphibian' or 'Insect'.
     * defaultmark 1. Stems are shuffled by default.
     * @return qtype_match_question
     */
    public static function make_a_ddmatching_question() {
        question_bank::load_question_definition_classes('ddmatch');
        $ddmatch = new qtype_ddmatch_question();
        test_question_maker::initialise_a_question($ddmatch);
        $ddmatch->name = 'Drag and drop matching question';
        $ddmatch->questiontext = 'Classify the animals.';
        $ddmatch->generalfeedback = 'Frogs and toads are amphibians, the others are mammals.';
        $ddmatch->qtype = question_bank::get_qtype('ddmatch');

        $ddmatch->shufflestems = 1;

        test_question_maker::set_standard_combined_feedback_fields($ddmatch);

        // Using unset to get 1-based arrays.
        $ddmatch->stems = array('', 'Dog', 'Frog', 'Toad', 'Cat');
        $ddmatch->stemformat = array('', FORMAT_HTML, FORMAT_HTML, FORMAT_HTML, FORMAT_HTML);
        $ddmatch->choices = array('', 'Mammal', 'Amphibian', 'Insect');
        $ddmatch->choiceformat = array('', FORMAT_HTML, FORMAT_HTML, FORMAT_HTML);
        $ddmatch->right = array('', 1, 2, 2, 1);
        unset($ddmatch->stems[0]);
        unset($ddmatch->stemformat[0]);
        unset($ddmatch->choices[0]);
        unset($ddmatch->right[0]);

        return $ddmatch;
    }

    /**
     * Makes a drag and drop matching question about classing some animals.
     * @return object the question definition data, as it might be returned from
     * get_question_options.
     */
    public function get_ddmatch_question_data_foursubq() {
        global $USER;
        $q = new stdClass();
        test_question_maker::initialise_question_data($q);
        $q->name = 'Drag and drop matching question';
        $q->qtype = 'ddmatch';
        $q->parent = 0;
        $q->questiontext = 'Classify the animals.';
        $q->questiontextformat = FORMAT_HTML;
        $q->generalfeedback = 'General feedback.';
        $q->generalfeedbackformat = FORMAT_HTML;
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;
        $q->length = 1;
        $q->hidden = 0;
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;

        $q->options = new stdClass();
        $q->options->shuffleanswers = 1;
        test_question_maker::set_standard_combined_feedback_fields($q->options);

        $q->options->subquestions = array(
            14 => (object) array(
                'id' => 14,
                'questiontext' => 'frog',
                'questiontextformat' => FORMAT_HTML,
                'answertext' => 'amphibian'),
            15 => (object) array(
                'id' => 15,
                'questiontext' => 'cat',
                'questiontextformat' => FORMAT_HTML,
                'answertext' => 'mammal'),
            16 => (object) array(
                'id' => 16,
                'questiontext' => 'newt',
                'questiontextformat' => FORMAT_HTML,
                'answertext' => 'amphibian'),
            17 => (object) array(
                'id' => 17,
                'questiontext' => '',
                'questiontextformat' => FORMAT_HTML,
                'answertext' => 'insect'),
        );

        return $q;
    }

    /**
     * Makes a drag and drop matching question about classing some animals.
     * @return object the question definition data, as it might be returned from
     *      the question editing form.
     */
    public function get_ddmatch_question_form_data_foursubq() {
        $q = new stdClass();
        $q->name = 'Drag and drop matching question';
        $q->questiontext = array('text' => 'Classify the animals.', 'format' => FORMAT_HTML);
        $q->generalfeedback = array('text' => 'General feedback.', 'format' => FORMAT_HTML);
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;

        $q->shuffleanswers = 1;
        test_question_maker::set_standard_combined_feedback_form_data($q);

        $q->subquestions = array(
            0 => array('text' => 'frog', 'format' => FORMAT_HTML),
            1 => array('text' => 'cat', 'format' => FORMAT_HTML),
            2 => array('text' => 'newt', 'format' => FORMAT_HTML),
            3 => array('text' => '', 'format' => FORMAT_HTML));

        $q->subanswers = array(
            0 => array('text' => 'amphibian', 'format' => FORMAT_HTML),
            1 => array('text' => 'mammal', 'format' => FORMAT_HTML),
            2 => array('text' => 'amphibian', 'format' => FORMAT_HTML),
            3 => array('text' => 'insect', 'format' => FORMAT_HTML),
        );

        $q->noanswers = 4;

        return $q;
    }

}