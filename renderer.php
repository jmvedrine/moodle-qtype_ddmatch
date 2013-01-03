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
 * Drag&drop matching question renderer class.
 *
 * @package    qtype_ddmatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for drag&drop matching questions.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Generate the HTML required for a ddmatch question
     *
     * @param $qa question_attempt The question attempt
     * @param $options question_display_options The options for display
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        // We use the question quite a lot so store a reference to it once.
        $question = $qa->get_question();

        // Put together the basic question text and answer block.
        $output  = '';
        $output .= $this->construct_questiontext($question->format_questiontext($qa));
        $output .= $this->construct_answerblock($qa, $question, $options);

        if ($this->can_use_drag_and_drop()) {
            $this->page->requires->string_for_js('draganswerhere', 'qtype_ddmatch');
            $this->page->requires->yui_module('moodle-qtype_ddmatch-dragdrop',
                    'M.qtype.ddmatch.init_dragdrop', array(array(
                        'questionid' => $qa->get_slot(),
                        'readonly' => $options->readonly,
                    ))
            );
        }

        if ($qa->get_state() === question_state::$invalid) {
            $response = $qa->get_last_qt_data();
            $output .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($response),
                    array('class' => 'validationerror'));
        }

        return $output;
    }

    /**
     * Check whether drag and drop is supported
     *
     * @return boolean Whether or not to generate the drag and drop content
     */
    protected function can_use_drag_and_drop() {
        global $USER, $CFG;

        // Note: The screenreader setting no longer exists from Moodle 2.4.
        if (!$CFG->enableajax || !empty($USER->screenreader)) {
            return false;
        }

        return true;
    }

    /**
     * Format the question choices for display
     *
     * @param question_attempt qa
     */
    public function format_choices(question_attempt $qa) {
        $question = $qa->get_question();
        $choices = array();
        foreach ($question->get_choice_order() as $key => $choiceid) {
            $choice = $question->choices[$choiceid];
            $choice = $question->format_text(
                    $choice, $question->choiceformat[$choiceid],
                    $qa, 'qtype_ddmatch', 'subanswer', $choiceid);
            $choices[$key] = $choice;
        }
        return $choices;
    }

    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    public function correct_response(question_attempt $qa) {
        if ($qa->get_state()->is_correct()) {
            // The answer was correct so we don't need to do anything further.
            return '';
        }

        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();
        $choices = $this->format_choices($qa, true);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable correctanswertable';
        $table->size = array('50%', '50%');
        foreach ($stemorder as $key => $stemid) {
            $row = new html_table_row();
            $row->cells[] = $question->format_text($question->stems[$stemid],
                    $question->stemformat[$stemid], $qa,
                    'qtype_ddmatch', 'subquestion', $stemid);
            $row->cells[] = $choices[$question->get_right_choice_for($stemid)];

            $table->data[] = $row;
        }

        return get_string('correctansweris', 'qtype_match', html_writer::table($table));
    }

    /**
     * Construct the question text displayed to the user
     *
     * @param questiontext The question text to user
     * @return String the rendered question text
     */
    public function construct_questiontext($questiontext) {
        return html_writer::tag('div', $questiontext, array(
                'class' => 'qtext',
        ));
    }

    /**
     * Construct the answer block area
     *
     * @param question_attempt $qa
     */
    public function construct_answerblock($qa, $question, $options) {
        $stemorder = $question->get_stem_order();
        $response = $qa->get_last_qt_data();
        $choices = $this->format_choices($qa);

        $o  = html_writer::start_tag('div', array('class' => 'ablock'));
        $o .= html_writer::start_tag('table', array('class' => 'answer'));
        $o .= html_writer::start_tag('tbody');

        $parity = 0;
        $curfieldname = null;
        foreach ($stemorder as $key => $stemid) {
            $o .= html_writer::start_tag('tr', array('class' => 'r' . $parity));
            $o .= html_writer::tag('td', $this->construct_stem_cell($qa, $question, $stemid),
                            array('class' => 'text'));

            $classes = array('control');
            $feedbackimage = '';

            $curfieldname = $question->get_field_name($key);
            if (array_key_exists($curfieldname, $response)) {
                $selected = (int) $response[$curfieldname];
            } else {
                $selected = 0;
            }
            $fraction = (int) ($selected && $selected == $question->get_right_choice_for($stemid));

            if ($options->correctness && $selected) {
                $classes[]  = $this->feedback_class($fraction);
                $feedbackimage = $this->feedback_image($fraction);
            }

            if ($this->can_use_drag_and_drop()) {
                $dragdropclasses = $classes;
                $classes[] = 'hiddenifjs';
                $dragdropclasses[] = 'visibleifjs';
            }

            $o .= html_writer::tag('td',
                    $this->construct_choice_cell_select($qa, $options, $choices, $stemid, $curfieldname, $selected) .
                    ' ' . $feedbackimage, array('class' => implode(' ', $classes)));

            if ($this->can_use_drag_and_drop()) {
                // Only add the dragdrop divs if drag drop is enabled.
                $o .= html_writer::tag('td',
                        $this->construct_choice_cell_dragdrop($qa, $options, $choices, $stemid, $curfieldname, $selected) .
                        ' ' . $feedbackimage, array('class' => implode(' ', $dragdropclasses)));
            }

            $o .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
        }
        $o .= html_writer::end_tag('tbody');
        $o .= html_writer::end_tag('table');

        if ($this->can_use_drag_and_drop()) {
            $o .= $this->construct_available_dragdrop_choices($qa, $question);
        }

        $o .= html_writer::end_tag('div');
        $o .= html_writer::tag('div', '', array('class' => 'clearer'));

        return $o;
    }

    private function construct_stem_cell($qa, $question, $stemid) {
        return $question->format_text(
                            $question->stems[$stemid], $question->stemformat[$stemid],
                            $qa, 'qtype_ddmatch', 'subquestion', $stemid);
    }

    private function construct_choice_cell_select($qa, $options, $choices, $stemid, $curfieldname, $selected) {
        return html_writer::select($choices, $qa->get_qt_field_name($curfieldname), $selected,
                            array('0' => 'choose'), array('disabled' => $options->readonly));
    }

    private function construct_choice_cell_dragdrop($qa, $options, $choices, $stemid, $curfieldname, $selected) {

        $placeholderclasses = array('placeholder');
        $li = '';

        // Check whether an answer has already been selected.
        if ($selected !== 0) {
            // An answer has already been selected, display it as well.
            $question = $qa->get_question();
            $choiceorder = $question->get_choice_order();

            $attributes = array(
                    'data-id' => $selected,
                    'class' => 'matchdrag copy');
            $li = html_writer::tag('li', $choices[$selected], $attributes);

            // Add the hidden placeholder class so that the placeholder is initially hidden.
            $placeholderclasses[] = 'hidden';
        }

        $placeholder = html_writer::tag('li', html_writer::tag('p',
            get_string('draganswerhere', 'qtype_ddmatch')), array(
            'class' => implode(' ', $placeholderclasses),
        ));
        $li = $placeholder . $li;

        $question = $qa->get_question();

        $attributes = array(
            'id'    => 'ultarget'.$question->id.'_'.$stemid,
            'name'  => $qa->get_qt_field_name($curfieldname),
            'class' => 'matchtarget matchdefault',
            'data-selectname' => $qa->get_qt_field_name($curfieldname),
        );
        $output = html_writer::tag('ul', $li, $attributes);

        return $output;
    }

    /**
     * Construct the list of available answers for use in the drag and drop
     * interface.
     *
     * @param $question
     * @return String
     */
    public function construct_available_dragdrop_choices($qa, $question) {
        $choiceorder = $question->get_choice_order();
        $choices = $this->format_choices($qa, true);

        $uldata = '';
        foreach ($choiceorder as $key => $choiceid) {
            $attributes = array(
                    'data-id' => $key,
                    'class' => 'matchdrag'
            );
            $li = html_writer::tag('li', $choices[$key], $attributes);
            $uldata .= $li;
        }
        $attributes = array(
            'id'    => 'ulorigin' . $question->id,
            'class' => 'matchorigin visibleifjs');

        $o = html_writer::tag('ul', $uldata, $attributes);

        return $o;
    }
}
