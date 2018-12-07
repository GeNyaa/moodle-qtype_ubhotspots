<?php
/**
 * BAClickToReveal lib.php
 *
 * @copyright  Bright Alley Knowledge and Learning
 * @author     Mannes Brak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
/**
 * The BAClickToReveal question class
 */
class qtype_ubhotspots extends question_type {
    public function is_real_question_type() {
        return false;
    }

    public function is_usable_by_random() {
        return false;
    }

    public function can_analyse_responses() {
        return false;
    }

   /**
     * @return boolean to indicate success of failure.
     */
    function get_question_options($question) {
        global $DB, $OUTPUT;
        if (!$question->options = $DB->get_record('qtype_ubhotspots', array('question' => $question->id))) {
            $OUTPUT->notification('Error: Missing question options for ubhotspots question'.$question->id.'!');
            return false;
        }
        
        if (!$question->options->answers = $DB->get_records('question_answers', array('question' => $question->id))) {
            $OUTPUT->notification('Error: Missing question answers for ubhotspots question'.$question->id.'!');
           return false;
        }
        
        return true;
    }

    function save_question($question, $form) {
        global $USER, $DB, $OUTPUT, $CFG;

        list($question->category) = explode(',', $form->category);
        $context = $this->get_context_by_category_id($question->category);

        // This default implementation is suitable for most
        // question types.

        // First, save the basic question itself.
        $question->name = trim($form->name);
        $question->parent = isset($form->parent) ? $form->parent : 0;
        $question->length = $this->actual_number_of_questions($question);
        $question->penalty = isset($form->penalty) ? $form->penalty : 0;

        // The trim call below has the effect of casting any strange values received,
        // like null or false, to an appropriate string, so we only need to test for
        // missing values. Be careful not to break the value '0' here.
        if (!isset($form->questiontext['text'])) {
            $question->questiontext = '';
        } else {
            $question->questiontext = trim($form->questiontext['text']);
        }
        $question->questiontextformat = !empty($form->questiontext['format']) ?
            $form->questiontext['format'] : 0;

        if (empty($form->generalfeedback['text'])) {
            $question->generalfeedback = '';
        } else {
            $question->generalfeedback = trim($form->generalfeedback['text']);
        }
        $question->generalfeedbackformat = !empty($form->generalfeedback['format']) ?
            $form->generalfeedback['format'] : 0;

        if (empty($question->name)) {
            $question->name = shorten_text(strip_tags($form->questiontext['text']), 15);
            if (empty($question->name)) {
                $question->name = '-';
            }
        }

        if ($question->penalty > 1 or $question->penalty < 0) {
            $question->errors['penalty'] = get_string('invalidpenalty', 'question');
        }

        $form->defaultmark = 0;
        if (isset($form->defaultmark)) {
            $question->defaultmark = $form->defaultmark;
        }

        // If the question is new, create it.
        if (empty($question->id)) {
            // Set the unique code.
            $question->stamp = make_unique_id_code();
            $question->createdby = $USER->id;
            $question->timecreated = time();
            $question->id = $DB->insert_record('question', $question);
        }

        // Now, whether we are updating a existing question, or creating a new
        // one, we have to do the files processing and update the record.
        // Question already exists, update.
        $question->modifiedby = $USER->id;
        $question->timemodified = time();

        if (!empty($question->questiontext) && !empty($form->questiontext['itemid'])) {
            $question->questiontext = file_save_draft_area_files($form->questiontext['itemid'],
                $context->id, 'question', 'questiontext', (int)$question->id,
                $this->fileoptions, $question->questiontext);
        }
        if (!empty($question->generalfeedback) && !empty($form->generalfeedback['itemid'])) {
            $question->generalfeedback = file_save_draft_area_files(
                $form->generalfeedback['itemid'], $context->id,
                'question', 'generalfeedback', (int)$question->id,
                $this->fileoptions, $question->generalfeedback);
        }
        $DB->update_record('question', $question);

        // Now to save all the answers and type-specific options.
        $form->id = $question->id;
        $form->qtype = $question->qtype;
        $form->category = $question->category;
        $form->questiontext = $question->questiontext;
        $form->questiontextformat = $question->questiontextformat;
        // Current context.
        $form->context = $context;

        /*
         * save the image
         */
        $draftitemid = file_get_submitted_draft_itemid('image');
        file_save_draft_area_files($draftitemid, $context->id, 'qtype_ubhotspots', 'image',
            $question->id, array('maxfiles' => 1,
                'maxbytes' => $CFG->maxbytes,
                'subdirs' => 0, 'accepted_types' => 'image'));


        $result = $this->save_question_options($form);

        if (!empty($result->error)) {
            print_error($result->error);
        }

        if (!empty($result->notice)) {
            notice($result->notice, "question.php?id=$question->id");
        }

        if (!empty($result->noticeyesno)) {
            throw new coding_exception(
                '$result->noticeyesno no longer supported in save_question.');
        }

        // Give the question a unique version stamp determined by question_hash().
        $DB->set_field('question', 'version', question_hash($question),
            array('id' => $question->id));

        return $question;
    }

    /**
     * Save the units and the answers associated with this question.
     * @return boolean to indicate success of failure.
     */
    function save_question_options($question) {
        global $DB;

        // Changed answers check to no longer strip html from the editor box.
        //$answers = json_decode(stripslashes($question->hseditordata));
        $answers = json_decode($question->hseditordata);
        $result = new stdClass();
        
        foreach($answers as $key=>$a){            
            if(!$a || !$a->draw || !$a->shape || !$a->text){
                unset($answers[$key]);
            }
        }
        
        if(!$answers){        
            $result->notice = get_string("failedloadinganswers", "qtype_ubhotspots");
            return $result;
        }
        
        if (!$oldanswers = $DB->get_records("question_answers", array("question" => $question->id), "id ASC")) {
            $oldanswers = array();
        }
        
        // TODO - Javascript Interface for fractions in the editor
        $fraction = round(1 / count($answers), 2);
        
        foreach($answers as $a){            
                         
            if ($answer = array_shift($oldanswers)) {  // Existing answer, so reuse it
                
                $answer->answer     = addslashes(json_encode($a));
                $answer->fraction   = $fraction;
                $answer->feedback = '';
                if (!$DB->update_record("question_answers", $answer)) {
                    $result->error = "Could not update quiz answer! (id=$answer->id)";
                    return $result;
                }
            } else {
                
                unset($answer);
                $answer = new stdClass();
                $answer->answer   = addslashes(json_encode($a));
                $answer->question = $question->id;
                $answer->fraction = $fraction;
                $answer->feedback = '';
                if (!$answer->id = $DB->insert_record("question_answers", $answer)) {
                    $result->error = "Could not insert quiz answer! ";
                    return $result;
                }
            }
            
        }
        
                
        // delete old answer records
        if (!empty($oldanswers)) {
            foreach($oldanswers as $oa) {
                $DB->delete_records('question_answers', array('id' => $oa->id));
            }
        }
        
        $update = true;
        $options = $DB->get_record("qtype_ubhotspots", array("question" => $question->id));
        if (!$options) {
            $update = false;
            $options = new stdClass;
            $options->question = $question->id;
        }
        
        $options->hseditordata = addslashes($question->hseditordata);
        $options->scrolltoresult = (int) isset($question->scrolltoresult);
        $options->highlightonhover = (int) isset($question->highlightonhover);
        
        if ($update) {
            if (!$DB->update_record("qtype_ubhotspots", $options)) {
                $result->error = "Could not update quiz ubhotspots options! (id=$options->id)";
                return $result;
            }
        } else {
            if (!$DB->insert_record("qtype_ubhotspots", $options)) {
                $result->error = "Could not insert quiz ubhotspots options!";
                return $result;
            }
        }        

        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @param integer $questionid The question being deleted
     * @return boolean to indicate success of failure.
     */
    function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records("qtype_ubhotspots", array("question" => $questionid));
        return true;
    }
    
    
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        $state->responses = array();
        return true;
    }

    function restore_session_and_responses(&$question, &$state) {
                        
        list($keys, $values) = explode(':',$state->responses['']);
        $state->responses = array_combine(explode(';',$keys),explode(';',$values));
        
        return true;
    }
    
    function save_session_and_responses(&$question, &$state) {
        global $DB;
        $responses = implode(';',array_keys($state->responses)).':';
        $responses .= implode(';', $state->responses);
    
        return $DB->set_field('question_states', 'answer', $responses, array('id', $state->id));
    }    
    
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        global $CFG;

        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';

        // Print formulation
        $questiontext = $this->format_text($question->questiontext,$question->questiontextformat, $cmoptions);
        $image = get_question_image($question, $cmoptions->course);
    
        
        $isfinished = question_state_is_graded($state->last_graded) || $state->event == QUESTION_EVENTCLOSE;
        $feedback = '';
        if ($isfinished && $options->generalfeedback){
            $feedback = $this->format_text($question->generalfeedback, $question->questiontextformat, $cmoptions);
        }
    
        $nameprefix = $question->name_prefix;
        
        $imgfeedback = array();
        
        if(($options->feedback || $options->correct_responses) && !empty($state->responses)){
            foreach ($state->responses as $key=>$response){
                if(isset($question->options->answers[$key]))
                    $imgfeedback[$key] = $this->check_coords($response,$question->options->answers[$key]->answer);                
            }
        }
                        
        include("$CFG->dirroot/question/type/ubhotspots/display.html");
        
    }
    
    function grade_responses(&$question, &$state, $cmoptions) {
        $state->raw_grade = 0;
               
        foreach ($state->responses as $key=>$response) {
            if ($this->check_coords($response,$question->options->answers[$key]->answer)) {
                $state->raw_grade += $question->options->answers[$key]->fraction;
            }
        }
       
       // Make sure we don't assign negative or too high marks
        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;

        // Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }
    

  /*  function get_all_responses(&$question, &$state) {
        $result = new stdClass;
        // TODO, return a link to a php that displays the correct response
        return $result;
    }

    function get_actual_response($question, $state) {
        // TODO, return a link to a php that displays the correct response
        $responses = '';
        return $responses;
    } */
    
    /**
     * Check if the user entered coords are inside the correct shape
     *
     * @param object $response The users response
     * @param object $answer The correct answer object containing the shape settings
     * @return boolean to indicate success of failure.
     */
    function check_coords($response, $answer){
                        
        if(!$response || !strpos($response,',')){
            return false;
        }        
        
        $answer = json_decode($answer);
        list($x,$y) = explode(',',$response);
        
        if($answer && $answer->shape){                        
            
            $s = $answer->shape;
            // Rectangle
            if($answer->shape->shape == 'rect'){
                
                if($x >= $s->startX && $x <= $s->endX && $y >= $s->startY && $y <= $s->endY){                    
                    return true;
                }
            }
            // Ellipse
            else if($answer->shape->shape == 'ellip'){
                $w = $s->endX - $s->startX;
                $h = $s->endY - $s->startY;
                                 
                // Ellipse radius
                $rx = $w / 2;
                $ry = $h / 2; 
        
                // Ellipse center
                $cx = $s->startX + $rx;
                $cy = $s->startY + $ry;
                    
                $dx = ($x - $cx) / $rx;
                $dy = ($y - $cy) / $ry;
                $distance = $dx * $dx + $dy * $dy;
                
                //if ((cuadrado(mouseX - cx)/cuadrado(w)) + (cuadrado(mouseY - cy)/cuadrado(h)) < 1)
                if ($distance < 1.0){
                    return true;
                }
            }    
        }        
        return false;
    }

    /**
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf,$preferences,$question,$level=6) {
        global $DB;
        $status = true;

        $ubhotspots = $DB->get_records("qtype_ubhotspots",array("question" => $question),"id");
        //If there are ubhotspots
        if ($ubhotspots) {
            //Iterate over each ubhotspots
            foreach ($ubhotspots as $hs) {
                $status = fwrite ($bf,start_tag("UBHOTSPOTS",$level,true));
                //Print ubhotspots contents
                fwrite ($bf,full_tag("HSEDITORDATA",$level+1,false,$hs->hseditordata));
                $status = fwrite ($bf,end_tag("UBHOTSPOTS",$level,true));
            }

            //Now print question_answers
            $status = question_backup_answers($bf,$preferences,$question);
        }
        return $status;

    }

    /**
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id,$new_question_id,$info,$restore) {
        global $DB;
        $status = true;

        //Get the ubhotspots array
        $ubhotspots = $info['#']['UBHOTSPOTS'];

        //Iterate over ubhotspots
        for($i = 0; $i < sizeof($ubhotspots); $i++) {
            $mul_info = $ubhotspots[$i];

            //Now, build the qtype_ubhotspots record structure
            $ubhotspot = new stdClass;
            $ubhotspot->question = $new_question_id;
            $ubhotspot->hseditordata = backup_todb($mul_info['#']['HSEDITORDATA']['0']['#']);                      

            //The structure is equal to the db, so insert the question_shortanswer
            $newid = $DB->insert_record ("qtype_ubhotspots",$ubhotspot);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }

        return $status;        
    }

    public function actual_number_of_questions($question) {
        // Used for the feature number-of-questions-per-page
        // to determine the actual number of questions wrapped by this question.
        // The question type description is not even a question
        // in itself so it will return ZERO!
        return 0;
    }

    public function get_random_guess_score($questiondata) {
        return null;
    }
}
