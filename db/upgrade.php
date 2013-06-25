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
 * Drag and drop matching question type upgrade code.
 *
 * @package    qtype_ddmatch
 * @copyright  2007 Adriane Boyd (adrianeboyd@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_qtype_ddmatch_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2010121800) {

        // Define field questiontextformat to be added to question_ddmatch_sub.
        $table = new xmldb_table('question_ddmatch_sub');
        $field = new xmldb_field('questiontextformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'questiontext');

        // Conditionally launch add field questiontextformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // In the past, question_ddmatch_sub.questiontext assumed to contain
        // content of the same form as question.questiontextformat. If we are
        // using the HTML editor, then convert FORMAT_MOODLE content to FORMAT_HTML.

        // Because this question type was updated later than the core types,
        // the available/relevant version dates make it hard to differentiate
        // early 2.0 installs from 1.9 updates, hence the extra check for
        // the presence of oldquestiontextformat.

        $table = new xmldb_table('question');
        $field = new xmldb_field('oldquestiontextformat');
        if ($dbman->field_exists($table, $field)) {
            $rs = $DB->get_recordset_sql('
                    SELECT qms.*, q.oldquestiontextformat
                    FROM {question_ddmatch_sub} qms
                    JOIN {question} q ON qms.question = q.id');
            foreach ($rs as $record) {
                if ($CFG->texteditors !== 'textarea' && $record->oldquestiontextformat == FORMAT_MOODLE) {
                    $record->questiontext = text_to_html($record->questiontext, false, false, true);
                    $record->questiontextformat = FORMAT_HTML;
                } else {
                    $record->questiontextformat = $record->oldquestiontextformat;
                }
                $DB->update_record('question_ddmatch_sub', $record);
            }
            $rs->close();
        }

        // Plugin ddmatch savepoint reached.
        upgrade_plugin_savepoint(true, 2010121800, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2011080300) {
        $table = new xmldb_table('question_ddmatch_sub');

        $field = new xmldb_field('answertext', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'questiontextformat');
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('answertextformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'answertext');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('question_ddmatch_sub',
                    array('answertextformat' => FORMAT_MOODLE), '', 'id,answertext,answertextformat');
            foreach ($rs as $s) {
                $s->answertext       = text_to_html($s->answertext, false, false, true);
                $s->answertextformat = FORMAT_HTML;
                $DB->update_record('question_ddmatch_sub', $s);
                upgrade_set_timeout();
            }
            $rs->close();
        }

        upgrade_plugin_savepoint(true, 2011080300, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2011080500) {

        $table = new xmldb_table('question_ddmatch');

        // Define field correctfeedback to be added to question_ddmatch.
        $field = new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'shuffleanswers');

        // Conditionally launch add field correctfeedback.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with ''.
            $DB->set_field('question_ddmatch', 'correctfeedback', '');

            // Now add the not null constraint.
            $field = new xmldb_field('correctfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'shuffleanswers');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field correctfeedbackformat to be added to question_ddmatch.
        $field = new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'correctfeedback');

        // Conditionally launch add field correctfeedbackformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field partiallycorrectfeedback to be added to question_ddmatch.
        $field = new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'correctfeedbackformat');

        // Conditionally launch add field partiallycorrectfeedback.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with ''.
            $DB->set_field('question_ddmatch', 'partiallycorrectfeedback', '');

            // Now add the not null constraint.
            $field = new xmldb_field('partiallycorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'correctfeedbackformat');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field partiallycorrectfeedbackformat to be added to question_ddmatch.
        $field = new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'partiallycorrectfeedback');

        // Conditionally launch add field partiallycorrectfeedbackformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field incorrectfeedback to be added to question_ddmatch.
        $field = new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null, 'partiallycorrectfeedbackformat');

        // Conditionally launch add field incorrectfeedback.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Now fill it with ''.
            $DB->set_field('question_ddmatch', 'incorrectfeedback', '');

            // Now add the not null constraint.
            $field = new xmldb_field('incorrectfeedback', XMLDB_TYPE_TEXT, 'small', null,
                    XMLDB_NOTNULL, null, null, 'partiallycorrectfeedbackformat');
            $dbman->change_field_notnull($table, $field);
        }

        // Define field incorrectfeedbackformat to be added to question_ddmatch.
        $field = new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'incorrectfeedback');

        // Conditionally launch add field incorrectfeedbackformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field shownumcorrect to be added to question_ddmatch.
        $field = new xmldb_field('shownumcorrect', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'incorrectfeedbackformat');

        // Conditionally launch add field shownumcorrect.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Plugin ddmatch savepoint reached.
        upgrade_plugin_savepoint(true, 2011080500, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062400) {

        // Define table question_ddmatch to be renamed to qtype_ddmatch_options.
        $table = new xmldb_table('question_ddmatch');

        // Launch rename table for qtype_ddmatch_options.
        $dbman->rename_table($table, 'qtype_ddmatch_options');

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062400, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062401) {

        // Define key question (foreign) to be dropped form qtype_ddmatch_options.
        $table = new xmldb_table('qtype_ddmatch_options');
        $key = new xmldb_key('question', XMLDB_KEY_FOREIGN, array('question'), 'question', array('id'));

        // Launch drop key question.
        $dbman->drop_key($table, $key);

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062401, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062402) {

        // Rename field question on table qtype_ddmatch_options to questionid.
        $table = new xmldb_table('qtype_ddmatch_options');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field question.
        $dbman->rename_field($table, $field, 'questionid');

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062402, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062403) {

        // Define key questionid (foreign-unique) to be added to qtype_ddmatch_options.
        $table = new xmldb_table('qtype_ddmatch_options');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, array('questionid'), 'question', array('id'));

        // Launch add key questionid.
        $dbman->add_key($table, $key);

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062403, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062404) {

        // Define field subquestions to be dropped from qtype_ddmatch_options.
        $table = new xmldb_table('qtype_ddmatch_options');
        $field = new xmldb_field('subquestions');

        // Conditionally launch drop field subquestions.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062404, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062405) {

        // Define table question_ddmatch_sub to be renamed to qtype_ddmatch_subquestions.
        $table = new xmldb_table('question_ddmatch_sub');

        // Launch rename table for qtype_ddmatch_subquestions.
        $dbman->rename_table($table, 'qtype_ddmatch_subquestions');

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062405, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062406) {

        // Define key question (foreign) to be dropped form qtype_ddmatch_subquestions.
        $table = new xmldb_table('qtype_ddmatch_subquestions');
        $key = new xmldb_key('question', XMLDB_KEY_FOREIGN, array('question'), 'question', array('id'));

        // Launch drop key question.
        $dbman->drop_key($table, $key);

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062406, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062407) {

        // Rename field question on table qtype_ddmatch_subquestions to questionid.
        $table = new xmldb_table('qtype_ddmatch_subquestions');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field question.
        $dbman->rename_field($table, $field, 'questionid');

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062407, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062408) {

        // Define key questionid (foreign) to be added to qtype_ddmatch_subquestions.
        $table = new xmldb_table('qtype_ddmatch_subquestions');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        // Launch add key questionid.
        $dbman->add_key($table, $key);

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062408, 'qtype', 'ddmatch');
    }

    if ($oldversion < 2013062409) {

        // Define field code to be dropped from qtype_ddmatch_subquestions.
        // The field code has not been needed since the new question engine in
        // Moodle 2.1. It should be safe to drop it now.
        $table = new xmldb_table('qtype_ddmatch_subquestions');
        $field = new xmldb_field('code');

        // Conditionally launch drop field code.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Record that qtype_ddmatch savepoint was reached.
        upgrade_plugin_savepoint(true, 2013062409, 'qtype', 'ddmatch');
    }

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
