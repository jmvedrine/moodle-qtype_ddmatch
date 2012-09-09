<?php

/**
 * Drag&drop matching question renderer class.
 *
 * @package    qtype
 * @subpackage ddmatch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for drag&drop matching questions.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmatch_renderer extends qtype_with_combined_feedback_renderer {

    public function head_code(question_attempt $qa) {
        global $PAGE;

        if ($this->can_use_drag_and_drop()) {
            $PAGE->requires->js('/question/type/ddmatch/dragdrop.js');

            $PAGE->requires->yui2_lib('yahoo');
            $PAGE->requires->yui2_lib('event');
            $PAGE->requires->yui2_lib('dom');
            $PAGE->requires->yui2_lib('dragdrop');
            $PAGE->requires->yui2_lib('animation');
        }
    }

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $creator = new formulation_and_controls_select($this, $qa, $options);
        $o = $creator->construct();

        if ($this->can_use_drag_and_drop()) {
            $creator = new formulation_and_controls_dragdrop($this, $qa, $options);
            $creator->construct();
            
            $initparams = json_encode($creator->get_ddmatch_init_params());
            $js = "YAHOO.util.Event.onDOMReady(function(){M.ddmatch.Init($initparams);});";

            $o .= html_writer::script($js);
        }

        return $o;
    }

    protected function can_use_drag_and_drop() {
        global $USER;

        $ie = check_browser_version('MSIE', 6.0);
        $ff = check_browser_version('Gecko', 20051106);
        $op = check_browser_version('Opera', 9.0);
        $sa = check_browser_version('Safari', 412);
        $ch = check_browser_version('Chrome', 6);

        if ((!$ie && !$ff && !$op && !$sa && !$ch) or !empty($USER->screenreader)) {
            return false;
        }

        return true;
    }

    public function format_choices(question_attempt $qa, $rawhtml=false) {
        $question = $qa->get_question();
        $choices = array();
        foreach ($question->get_choice_order() as $key => $choiceid) {
            $choice = $question->choices[$choiceid];
            $choice = $question->format_text(
                    $choice, $question->choiceformat[$choiceid],
                    $qa, 'qtype_ddmatch', 'subanswer', $choiceid);
            if ($rawhtml) $choices[$key] = $choice;
            else $choices[$key] = htmlspecialchars($choice);
        }
        return $choices;
    }

    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    public function correct_response(question_attempt $qa) {
        if ($qa->get_state()->is_correct()) return '';

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
    // needed for formulation_and_controls_* classes
    public function feedback_class($fraction) {
        return parent::feedback_class($fraction);
    }

    public function feedback_image($fraction, $selected = true) {
        return parent::feedback_image($fraction, $selected);
    }
}


abstract class formulation_and_controls_base {
    protected $ddmatchrenderer;
    protected $qa;
    protected $options;
    protected $question;
    protected $choices;
    protected $curfieldname;

    public function __construct(qtype_ddmatch_renderer $ddmatchrenderer,
            question_attempt $qa, question_display_options $options) {
        $this->ddmatchrenderer = $ddmatchrenderer;
        $this->qa = $qa;
        $this->options = $options;
        $this->question = $qa->get_question();
        $this->init_choices();
    }

    public function construct() {
        $response = $this->qa->get_last_qt_data();

        $result = '';
        $result .= $this->construct_qtext();

        $result .= $this->construct_ablock();

        if ($this->qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $this->question->get_validation_error($response),
                    array('class' => 'validationerror'));
        }

        return $result;
    }
    
    protected function construct_qtext() {
        return html_writer::tag('div', $this->question->format_questiontext($this->qa),
                array('class' => 'qtext'));
    }
    
    protected function construct_ablock() {
        $stemorder = $this->question->get_stem_order();
        $response = $this->qa->get_last_qt_data();

        $o = html_writer::start_tag('div', array('id' => 'ablock_'.$this->question->id, 'class' => 'ablock'));
        $o .= html_writer::start_tag('table', array('class' => 'answer'));
        $o .= html_writer::start_tag('tbody');

        $parity = 0;
        foreach ($stemorder as $key => $stemid) {
            $o .= html_writer::start_tag('tr', array('class' => 'r' . $parity));
            $o .= html_writer::tag('td', $this->construct_stem_cell($stemid),
                            array('class' => 'text'));

            $classes = 'control';
            $feedbackimage = '';

            $this->curfieldname = $this->question->get_field_name($key);
            if (array_key_exists($this->curfieldname, $response)) {
                $selected = $response[$this->curfieldname];
            } else {
                $selected = 0;
            }

            $fraction = (int) ($selected && $selected == $this->question->get_right_choice_for($stemid));

            if ($this->options->correctness && $selected) {
                $classes .= ' ' . $this->ddmatchrenderer->feedback_class($fraction);
                $feedbackimage = $this->ddmatchrenderer->feedback_image($fraction);
            }

            $o .= html_writer::tag('td',
                    $this->construct_choice_cell($stemid, $selected) .
                    ' ' . $feedbackimage, array('class' => $classes));

            $o .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
        }
        $o .= html_writer::end_tag('tbody');
        $o .= html_writer::end_tag('table');

        $o .= $this->construct_additional_controls();

        $o .= html_writer::end_tag('div');
        $o .= html_writer::tag('div', '', array('class' => 'clearer'));
        
        return $o;
    }

    protected function init_choices() {
        
    }

    protected function construct_stem_cell($stemid) {
        return $this->question->format_text(
                            $this->question->stems[$stemid], $this->question->stemformat[$stemid],
                            $this->qa, 'qtype_ddmatch', 'subquestion', $stemid);
    }

    protected function construct_choice_cell($stemid, $selected) {
        throw new coding_exception('construct_choice_cell must return at least empty string');
    }

    protected function construct_additional_controls() {
        return '';
    }
}

class formulation_and_controls_select extends formulation_and_controls_base {
    protected function init_choices() {
        $this->choices = $this->ddmatchrenderer->format_choices($this->qa);
    }

    protected function construct_choice_cell($stemid, $selected) {
        return html_writer::select($this->choices, $this->qa->get_qt_field_name($this->curfieldname), $selected,
                            array('0' => 'choose'), array('disabled' => $this->options->readonly));
    }
}

class formulation_and_controls_dragdrop extends formulation_and_controls_base {
    private $lastpostfix = 0;
    private $selectedids = array();
    private $ablock;
    
    protected function construct_ablock() {
        $this->ablock = parent::construct_ablock();
        
        return $this->ablock;
    }
    
    protected function init_choices() {
        $this->choices = $this->ddmatchrenderer->format_choices($this->qa, true);
    }

    protected function construct_choice_cell($stemid, $selected) {
        if ($selected == 0) {
            $li = html_writer::tag('li', get_string('draganswerhere', 'qtype_ddmatch'));
        }
        else {
            $choiceorder = $this->question->get_choice_order();
            $this->lastpostfix++;
            
            $id = 'drag'.$this->question->id.'_'.$selected.'_'.$this->lastpostfix;
            $this->selectedids[] = $id;
            $attributes = array(
                    'id'    => $id,
                    'name'  => $id,
                    'class' => 'matchdrag');
            $li = html_writer::tag('li', $this->choices[$selected], $attributes);
        }

        $attributes = array(
                'id'    => 'ultarget'.$this->question->id.'_'.$stemid,
                'name'  => $this->qa->get_qt_field_name($this->curfieldname),
                'class' => 'matchtarget matchdefault');
        $o = html_writer::tag('ul', $li, $attributes);
        
        $attributes = array(
                'type'  => 'hidden',
                'id'    => $this->qa->get_qt_field_name($this->curfieldname),
                'name'  => $this->qa->get_qt_field_name($this->curfieldname),
                'value' => $selected);
        $o .= html_writer::empty_tag('input', $attributes);
        
        return $o;
    }

    protected function construct_additional_controls() {
        $choiceorder = $this->question->get_choice_order();
        $uldata = '';
        foreach ($choiceorder as $key => $choiceid) {
            $attributes = array(
                    'id'    => 'drag'.$this->question->id.'_'.$key,
                    'name'  => 'drag'.$this->question->id.'_'.$key,
                    'class' => 'matchdrag');
            $li = html_writer::tag('li', $this->choices[$key], $attributes);
            $uldata .= $li;
        }
        $attributes = array(
            'id'    => 'ulorigin'.$this->question->id,
            'class' => 'matchorigin');

        $o = html_writer::tag('ul', $uldata, $attributes);

        return $o;
    }
    
    public function get_ddmatch_init_params() {
        $initparams = new stdClass();
        $initparams->id = $this->question->id;
        $initparams->stems = $this->question->get_stem_order();
        $initparams->choices = $this->question->get_choice_order();
        $initparams->selectedids = $this->selectedids;
        $initparams->readonly = $this->options->readonly;
        $initparams->dragstring = get_string('draganswerhere', 'qtype_ddmatch');
        $initparams->ablock = $this->ablock;

        return $initparams;
    }
}
