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
 * Question type class for the drag&drop matching question type.
 *
 * @package    qtype_ddmatch
 * @copyright  2007 Adriane Boyd (adrianeboyd@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');


/**
 * The drag&drop matching question type class.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch extends question_type {

    public function get_question_options($question) {
        global $DB;
        parent::get_question_options($question);
        $question->options = $DB->get_record('qtype_ddmatch_options',
                array('questionid' => $question->id));
        $question->options->subquestions = $DB->get_records('qtype_ddmatch_subquestions',
                array('questionid' => $question->id), 'id ASC');
        return true;
    }

    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();

        $oldsubquestions = $DB->get_records('qtype_ddmatch_subquestions',
                array('questionid' => $question->id), 'id ASC');

        // Insert all the new question & answer pairs.
        foreach ($question->subquestions as $key => $questiontext) {
            if ($questiontext['text'] == '' && trim($question->subanswers[$key]['text']) == '') {
                continue;
            }
            if ($questiontext['text'] != '' && trim($question->subanswers[$key]['text']) == '') {
                $result->notice = get_string('nomatchinganswer', 'qtype_match', $questiontext['text']);
            }

            // Update an existing subquestion if possible.
            $subquestion = array_shift($oldsubquestions);
            if (!$subquestion) {
                $subquestion = new stdClass();
                $subquestion->questionid = $question->id;
                $subquestion->questiontext = '';
                $subquestion->answertext = '';
                $subquestion->id = $DB->insert_record('qtype_ddmatch_subquestions', $subquestion);
            }

            $subquestion->questiontext = $this->import_or_save_files($questiontext,
                    $context, 'qtype_ddmatch', 'subquestion', $subquestion->id);
            $subquestion->questiontextformat = $questiontext['format'];
            $subquestion->answertext = $this->import_or_save_files($question->subanswers[$key],
                    $context, 'qtype_ddmatch', 'subanswer', $subquestion->id);
            $subquestion->answertextformat = $question->subanswers[$key]['format'];

            $DB->update_record('qtype_ddmatch_subquestions', $subquestion);
        }

        // Delete old subquestions records.
        $fs = get_file_storage();
        foreach ($oldsubquestions as $oldsub) {
            $fs->delete_area_files($context->id, 'qtype_ddmatch', 'subquestion', $oldsub->id);
            $fs->delete_area_files($context->id, 'qtype_ddmatch', 'subanswer', $oldsub->id);
            $DB->delete_records('qtype_ddmatch_subquestions', array('id' => $oldsub->id));
        }

        // Save the question options.
        $options = $DB->get_record('qtype_ddmatch_options', array('questionid' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('qtype_ddmatch_options', $options);
        }

        $options->shuffleanswers = $question->shuffleanswers;
        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('qtype_ddmatch_options', $options);

        $this->save_hints($question, true);

        if (!empty($result->notice)) {
            return $result;
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->shufflestems = $questiondata->options->shuffleanswers;
        $this->initialise_combined_feedback($question, $questiondata, true);

        $question->stems = array();
        $question->choices = array();
        $question->right = array();

        foreach ($questiondata->options->subquestions as $matchsub) {
            $ans = $matchsub->answertext;
            $key = array_search($matchsub->answertext, $question->choices);
            if ($key === false) {
                $key = $matchsub->id;
                $question->choices[$key] = $matchsub->answertext;
                $question->choiceformat[$key] = $matchsub->answertextformat;
            }

            if ($matchsub->questiontext !== '') {
                $question->stems[$matchsub->id] = $matchsub->questiontext;
                $question->stemformat[$matchsub->id] = $matchsub->questiontextformat;
                $question->right[$matchsub->id] = $key;
            }
        }
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_ddmatch_options', array('questionid' => $questionid));
        $DB->delete_records('qtype_ddmatch_subquestions', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        $q = $this->make_question($questiondata);
        return 1 / count($q->choices);
    }

    public function get_possible_responses($questiondata) {
        $subqs = array();

        $q = $this->make_question($questiondata);

        foreach ($q->stems as $stemid => $stem) {

            $responses = array();
            foreach ($q->choices as $choiceid => $choice) {
                $stemhtml = $q->html_to_text($stem, $q->stemformat[$stemid]);
                $choicehtml = $q->html_to_text($choice, $q->choiceformat[$choiceid]);

                $responses[$choiceid] = new question_possible_response(
                         $stemhtml. ': ' . $choicehtml,
                        ($choiceid == $q->right[$stemid]) / count($q->stems));
            }
            $responses[null] = question_possible_response::no_response();

            $subqs[$stemid] = $responses;
        }

        return $subqs;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        global $DB;
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);

        $subquestionids = $DB->get_records_menu('qtype_ddmatch_subquestions',
                array('questionid' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->move_area_files_to_new_context($oldcontextid,
                    $newcontextid, 'qtype_ddmatch', 'subquestion', $subquestionid);
            $fs->move_area_files_to_new_context($oldcontextid,
                    $newcontextid, 'qtype_ddmatch', 'subanswer', $subquestionid);
        }

        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        global $DB;
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);

        $subquestionids = $DB->get_records_menu('qtype_ddmatch_subquestions',
                array('questionid' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->delete_area_files($contextid, 'qtype_ddmatch', 'subquestion', $subquestionid);
            $fs->delete_area_files($contextid, 'qtype_ddmatch', 'subanswer', $subquestionid);
        }

        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    /**
     * Provide export functionality for xml format
     * @param question object the question object
     * @param format object the format object so that helper methods can be used
     * @param extra mixed any additional format specific data that may be passed by the format (see format code for info)
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $expout = '';
        $fs = get_file_storage();
        $contextid = $question->contextid;
        $expout .= $format->write_combined_feedback($question->options,
                                                    $question->id,
                                                    $question->contextid);
        foreach ($question->options->subquestions as $subquestion) {
            $files = $fs->get_area_files($contextid, 'qtype_ddmatch', 'subquestion', $subquestion->id);
            $textformat = $format->get_format($subquestion->questiontextformat);
            $expout .= "    <subquestion format=\"$textformat\">\n";
            $expout .= '      ' . $format->writetext( $subquestion->questiontext );
            $expout .= '      ' . $format->write_files($files, 2);
            $expout .= "       <answer format=\"$textformat\">\n";
            $expout .= '      ' . $format->writetext( $subquestion->answertext );
            $files = $fs->get_area_files($contextid, 'qtype_ddmatch', 'subanswer', $subquestion->id);
            $expout .= '      ' . $format->write_files($files, 2);
            $expout .= "       </answer>\n";
            $expout .= "    </subquestion>\n";
        }

        return $expout;
    }

    /**
     * Provide import functionality for xml format
     * @param $xml mixed the segment of data containing the question
     * @param $fromform object question object processed (so far) by standard import code
     * @param $format object the format object so that helper methods can be used (in particular error() )
     * @param $extra mixed any additional format specific data that may be passed by the format (see format code for info)
     * @return object question object suitable for save_options() call or false if cannot handle
     */
    public function import_from_xml($xml, $fromform, qformat_xml $format, $extra=null) {
        // Check question is for us.
        $qtype = $xml['@']['type'];
        if ($qtype == 'ddmatch') {
            $fromform = $format->import_headers($xml);

            // Header parts particular to ddmatch qtype.
            $fromform->qtype = $this->name();
            $fromform->shuffleanswers = $format->getpath( $xml, array( '#', 'shuffleanswers', 0, '#' ), 1 );

            // Run through subquestions.
            $fromform->subquestions = array();
            $fromform->subanswers = array();
            foreach ($xml['#']['subquestion'] as $subqxml) {
                $fromform->subquestions[] = $format->import_text_with_files($subqxml,
                        array(), '', $format->get_format($fromform->questiontextformat));

                $answers = $format->getpath($subqxml, array('#', 'answer', 0), array());
                $fromform->subanswers[] = $format->import_text_with_files($answers,
                        array(), '', $format->get_format($fromform->questiontextformat));
            }

            $format->import_combined_feedback($fromform, $xml, true);
            $format->import_hints($fromform, $xml, true);
            return $fromform;
        } else {
            return false;
        }
    }
}
