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
 * @package    qtype
 * @subpackage ddmatch
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the drag and rop matching question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_test_helper {
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
}
