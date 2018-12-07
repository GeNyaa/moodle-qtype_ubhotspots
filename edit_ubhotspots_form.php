<?php
/**
 * BAClickToReveal The editing form code for this question type.
 *
 * @copyright  Bright Alley Knowledge and Learning
 * @author     Mannes Brak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * ubhotspots editing form definition.
 * 
 * See http://docs.moodle.org/en/Development:lib/formslib.php for information
 * about the Moodle forms library, which is based on the HTML Quickform PEAR library.
 */
class qtype_ubhotspots_edit_form extends question_edit_form {

    function definition_inner($mform) {
        global $CFG, $PAGE;
        
        $mform->addElement('header', 'ubhotspotsheader', get_string('ubhotspots', 'qtype_ubhotspots'));
        $mform->setExpanded('ubhotspotsheader');
        $mform->addElement('filemanager', 'image', get_string('file'), null,
            array('maxfiles' => 1,
                'maxbytes' => $CFG->maxbytes,
                'subdirs' => 0, 'accepted_types' => 'image'));

        $mform->addElement('button', 'buttoneditor', get_string('openeditor', 'qtype_ubhotspots'), array('onclick'=>'hscheckImages(\''.(get_string('imagealert','qtype_ubhotspots')).'\',\''.(get_string('chooseanimage','qtype_ubhotspots')).'\',\''.$CFG->wwwroot.'\',this.form)'));
        
        $mform->addElement('hidden', 'hseditordata');
        $mform->setType('hseditordata', PARAM_RAW);

        // We don't need this default element.
        $mform->removeElement('defaultmark');
        $mform->addElement('hidden', 'defaultmark', 0);
        $mform->setType('defaultmark', PARAM_RAW);

        $mform->addElement('checkbox', 'scrolltoresult', get_string('scrolltoresult', 'qtype_ubhotspots'));
        $mform->addElement('checkbox', 'highlightonhover', get_string('highlightonhover', 'qtype_ubhotspots'));

        $PAGE->requires->jquery();
        $PAGE->requires->js(new moodle_url('/question/type/ubhotspots/js/script.js'));
    }

    function set_data($question) {
        global $CFG, $DB;
        $contextid = $DB->get_field('question_categories', 'contextid', array('id'=>$question->category));
        $context = context::instance_by_id($contextid, IGNORE_MISSING);

        if(isset($question->options)){
            $default_values['hseditordata'] =  stripslashes($question->options->hseditordata);
            $default_values['scrolltoresult'] =  $question->options->scrolltoresult;
            $default_values['highlightonhover'] =  $question->options->highlightonhover;
            $question = (object)((array)$question + $default_values);
        }
        if (isset($question->id)) {
        $draftitemid = file_get_submitted_draft_itemid('image');

        file_prepare_draft_area($draftitemid, $context->id, 'qtype_ubhotspots', 'image', $question->id,
            array('maxfiles' => 1,
                'maxbytes' => $CFG->maxbytes,
                'subdirs' => 0, 'accepted_types' => 'image'));

        $question->image = $draftitemid;
        }

        parent::set_data($question);
    }

    function validation($fromform, $files) {
        $errors = array();

        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }

    function qtype() {
        return 'ubhotspots';
    }
}
?>