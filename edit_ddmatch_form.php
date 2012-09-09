<?php

/**
 * Defines the editing form for the drag&drop match question type.
 *
 * @package    qtype
 * @subpackage ddmatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Drag&drop match question type editing form definition.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_edit_form extends question_edit_form {

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = $mform->createElement('editor', 'subquestions',
                get_string('question'), array('rows'=>3), $this->editoroptions);
        $repeated[] = $mform->createElement('editor', 'subanswers',
                get_string('answer'), array('rows'=>3), $this->editoroptions);
        $repeatedoptions['subquestions']['type'] = PARAM_RAW;
        $repeatedoptions['subanswers']['type'] = PARAM_RAW;
        $answersoption = 'subquestions';
        return $repeated;
    }

    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {
        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffle', 'qtype_match'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffle', 'qtype_match');
        $mform->setDefault('shuffleanswers', 1);

        $mform->addElement('static', 'answersinstruct',
                get_string('availablechoices', 'qtype_match'),
                get_string('filloutthreeqsandtwoas', 'qtype_match'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('questionno', 'question', '{no}'), 0);

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (empty($question->options)) {
            return $question;
        }

        $question->shuffleanswers = $question->options->shuffleanswers;

        $key = 0;
        foreach ($question->options->subquestions as $subquestion) {
            $question->subanswers[$key] = $subquestion->answertext;

            $draftid = file_get_submitted_draft_itemid('subquestions[' . $key . ']');
            $question->subquestions[$key] = array();
            $question->subquestions[$key]['text'] = file_prepare_draft_area(
                $draftid,           // draftid
                $this->context->id, // context
                'qtype_ddmatch',      // component
                'subquestion',      // filarea
                !empty($subquestion->id) ? (int) $subquestion->id : null, // itemid
                $this->fileoptions, // options
                $subquestion->questiontext // text
            );
            $question->subquestions[$key]['format'] = $subquestion->questiontextformat;
            $question->subquestions[$key]['itemid'] = $draftid;

            $draftid = file_get_submitted_draft_itemid('subanswers[' . $key . ']');
            $question->subanswers[$key] = array();
            $question->subanswers[$key]['text'] = file_prepare_draft_area(
                $draftid,           // draftid
                $this->context->id, // context
                'qtype_ddmatch',      // component
                'subanswer',      // filarea
                !empty($subquestion->id) ? (int) $subquestion->id : null, // itemid
                $this->fileoptions, // options
                $subquestion->answertext // text
            );
            $question->subanswers[$key]['format'] = $subquestion->answertextformat;
            $question->subanswers[$key]['itemid'] = $draftid;
            
            $key++;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['subanswers'];
        $questions = $data['subquestions'];
        $questioncount = 0;
        $answercount = 0;
        foreach ($questions as $key => $question) {
            $trimmedquestion = trim($question['text']);
            $trimmedanswer = trim($answers[$key]['text']);
            if ($trimmedquestion != '') {
                $questioncount++;
            }
            if ($trimmedanswer != '' || $trimmedquestion != '') {
                $answercount++;
            }
            if ($trimmedquestion != '' && $trimmedanswer == '') {
                $errors['subanswers['.$key.']'] =
                        get_string('nomatchinganswerforq', 'qtype_match', $trimmedquestion);
            }
        }
        $numberqanda = new stdClass();
        $numberqanda->q = 2;
        $numberqanda->a = 3;
        if ($questioncount < 1) {
            $errors['subquestions[0]'] =
                    get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        if ($questioncount < 2) {
            $errors['subquestions[1]'] =
                    get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        if ($answercount < 3) {
            $errors['subanswers[2]'] =
                    get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        return $errors;
    }

    public function qtype() {
        return 'ddmatch';
    }
}
