<?php
/**
 * BAClickToReveal The editing form code for this question type.
 *
 * @copyright  Bright Alley Knowledge and Learning
 * @author     Mannes Brak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class qtype_ubhotspots_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
                                             question_display_options $options) {
        global $PAGE, $DB, $CFG, $OUTPUT;

        $question = $qa->get_question();
        $questiontext = $question->format_questiontext($qa);

        if (!$question->options = $DB->get_record('qtype_ubhotspots', array('question' => $question->id))) {
            $OUTPUT->notification('Error: Missing question options for ubhotspots question'.$question->id.'!');
            return false;
        }

        if (!$question->options->answers = $DB->get_records('question_answers', array('question' => $question->id))) {
            $OUTPUT->notification('Error: Missing question answers for ubhotspots question'.$question->id.'!');
            return false;
        }

        list($category) = explode(',', $question->category);
        $contextid = $DB->get_field('question_categories', 'contextid', array('id'=>$category));
        $context = context::instance_by_id($contextid, IGNORE_MISSING);

        $nameprefix = '';
        $state = $qa->get_state();

        /*
         * Find the file belonging to the question
         */
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'qtype_ubhotspots', 'image', $question->id);
        $filename = "";
        foreach($files as $file) {
            if ($file->get_filesize() > 0) {
                $filename = $file->get_filename();
                break;
            }
        }

        $image = moodle_url::make_pluginfile_url($context->id, 'qtype_ubhotspots', 'image', $question->id, '/', $filename);

        $image = $image->out();

        $wwwr = $CFG->wwwroot.'/question/type/ubhotspots/';
        $hsufix = $question->id;

        // This is for avoid problems with image cache, see
        // http://www.thefutureoftheweb.com/blog/image-onload-isnt-being-called
        // http://api.jquery.com/load-event/
        $imgsufix = random_string();
        $hotspots = array();
        $texts = array();
        foreach($question->options->answers as $key=>$a){
            $a = json_decode(stripslashes($a->answer));

            if ($a->draw == 1) {
                $texts[] = $a->text;
                if ($a->shape->startX > $a->shape->endX) {
                    $t = $a->shape->startX;
                    $a->shape->startX = $a->shape->endX;
                    $a->shape->endX = $t;
                }
                if ($a->shape->startY > $a->shape->endY) {
                    $t = $a->shape->startY;
                    $a->shape->startY = $a->shape->endY;
                    $a->shape->endY = $t;
                }
                $hotspots[] = $a->shape;
            }
        }

        /*
         * The following is a bit dirty...
         */
        ob_start();
        include($CFG->dirroot.'/question/type/ubhotspots/display.html');
        $result = ob_get_clean();

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        // TODO.
        return '';
    }

    public function correct_response(question_attempt $qa) {
        // TODO.
        return '';
    }
}