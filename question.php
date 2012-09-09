<?php
/**
 * Drag-and-drop matching question type classe.
 *
 * @package    qtype
 * @subpackage ddmatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/match/question.php');

/**
 * Represents a drag&drop matching question.
 * Based on core matching question.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_question extends qtype_match_question {

    public function get_question_summary() {
        $question = $this->html_to_text($this->questiontext, $this->questiontextformat);
        
        $stems = array();
        foreach ($this->stemorder as $stemid) {
            $stems[] = $this->html_to_text($this->stems[$stemid], $this->stemformat[$stemid]);
        }
        
        $choices = array();
        foreach ($this->choiceorder as $choiceid) {
            $choices[] = $this->html_to_text($this->choices[$choiceid], $this->choiceformat[$choiceid]);
        }
        
        return $question . ' {' . implode('; ', $stems) . '} -> {' .
                implode('; ', $choices) . '}';
    }

    public function summarise_response(array $response) {
        $matches = array();
        foreach ($this->stemorder as $key => $stemid) {
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)]) {
                $stemssummarise = $this->html_to_text($this->stems[$stemid],
                        $this->stemformat[$stemid]);
                
                $choiceid = $this->choiceorder[$response[$this->field($key)]];
                $choicesummarise = $this->html_to_text($this->choices[$choiceid],
                        $this->stemformat[$choiceid]);
                $matches[] =  $stemssummarise. ' -> ' .$choicesummarise;
            }
        }
        
        if (empty($matches)) {
            return null;
        }
        
        return implode('; ', $matches);
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'qtype_ddmatch' && $filearea == 'subquestion' or
                $component == 'qtype_ddmatch' && $filearea == 'subanswer') {
            $subqid = reset($args);
            return array_key_exists($subqid, $this->stems);
        } else if ($component == 'question' && in_array($filearea,
                array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'))) {
            return $this->check_combined_feedback_file_access($qa, $options, $filearea);

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    public function get_field_name($key) {
        return $this->field($key);
    }

}
