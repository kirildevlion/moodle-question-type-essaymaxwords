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
 * Defines the editing form for the essaymaxwords question type.
 *
 * @package    qtype
 * @subpackage essaymaxwords
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * essaymaxwords question type editing form.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essaymaxwords_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('essaymaxwords');

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_essaymaxwords'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('text', 'responseminwords',
                get_string('responseminwords', 'qtype_essaymaxwords'));
        $mform->addHelpButton('responseminwords', 'minwords_message_admin', 'qtype_essaymaxwords');
        $mform->disabledIf('responseminwords', 'minwords_message_admin', 'eq', 0);                    
        
        $mform->addElement('text', 'responsemaxwords',
                get_string('responsemaxwords', 'qtype_essaymaxwords'));
        $mform->addHelpButton('responsemaxwords', 'maxwords_message_admin', 'qtype_essaymaxwords');
        $mform->disabledIf('responsemaxwords', 'maxwords_message_admin', 'eq', 0);                

        $mform->addElement('select', 'responseformat',
                get_string('responseformat', 'qtype_essaymaxwords'), $qtype->response_formats());
        $mform->setDefault('responseformat', 'editor');

        $mform->addElement('select', 'responserequired',
                get_string('responserequired', 'qtype_essaymaxwords'), $qtype->response_required_options());
        $mform->setDefault('responserequired', 1);
        $mform->disabledIf('responserequired', 'responseformat', 'eq', 'noinline');

        $mform->addElement('select', 'responsefieldlines',
                get_string('responsefieldlines', 'qtype_essaymaxwords'), $qtype->response_sizes());
        $mform->setDefault('responsefieldlines', 15);
        $mform->disabledIf('responsefieldlines', 'responseformat', 'eq', 'noinline');

        $mform->addElement('select', 'attachments',
                get_string('allowattachments', 'qtype_essaymaxwords'), $qtype->attachment_options());
        $mform->setDefault('attachments', 0);

        $mform->addElement('select', 'attachmentsrequired',
                get_string('attachmentsrequired', 'qtype_essaymaxwords'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 0);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_essaymaxwords');
        $mform->disabledIf('attachmentsrequired', 'attachments', 'eq', 0);

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_essaymaxwords'));
        $mform->addElement('editor', 'responsetemplate', get_string('responsetemplate', 'qtype_essaymaxwords'),
                array('rows' => 10),  array_merge($this->editoroptions, array('maxfiles' => 0)));
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_essaymaxwords');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_essaymaxwords'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_essaymaxwords'),
                array('rows' => 10), $this->editoroptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }
        
        $question->responsemaxwords = $question->options->responsemaxwords;
        $question->responseminwords = $question->options->responseminwords;
        $question->responseformat = $question->options->responseformat;
        $question->responserequired = $question->options->responserequired;
        $question->responsefieldlines = $question->options->responsefieldlines;
        $question->attachments = $question->options->attachments;
        $question->attachmentsrequired = $question->options->attachmentsrequired;

        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $question->graderinfo = array();
        $question->graderinfo['text'] = file_prepare_draft_area(
            $draftid,           // Draftid
            $this->context->id, // context
            'qtype_essaymaxwords',      // component
            'graderinfo',       // filarea
            !empty($question->id) ? (int) $question->id : null, // itemid
            $this->fileoptions, // options
            $question->options->graderinfo // text.
        );
        $question->graderinfo['format'] = $question->options->graderinfoformat;
        $question->graderinfo['itemid'] = $draftid;

        $question->responsetemplate = array(
            'text' => $question->options->responsetemplate,
            'format' => $question->options->responsetemplateformat,
        );

        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Don't allow both 'no inline response' and 'no attachments' to be selected,
        // as these options would result in there being no input requested from the user.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachments']) {
            $errors['attachments'] = get_string('mustattach', 'qtype_essaymaxwords');
        }

        // If 'no inline response' is set, force the teacher to require attachments;
        // otherwise there will be nothing to grade.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachmentsrequired']) {
            $errors['attachmentsrequired'] = get_string('mustrequire', 'qtype_essaymaxwords');
        }

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['attachments'] != -1 && $fromform['attachments'] < $fromform['attachmentsrequired'] ) {
            $errors['attachmentsrequired']  = get_string('mustrequirefewer', 'qtype_essaymaxwords');
        }
        
        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['responsemaxwords'] && !is_numeric($fromform['responsemaxwords']) ) {
            $errors['responsemaxwords']  = get_string('maxwords_message_admin', 'qtype_essaymaxwords');                  
        }  
        
        if ($fromform['responsemaxwords'] && is_numeric($fromform['responsemaxwords']) && $fromform['responsemaxwords']<0) {
            $errors['responsemaxwords']  = get_string('maxwords_positive_message_admin', 'qtype_essaymaxwords');                  
        }         
        
        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['responseminwords'] && !is_numeric($fromform['responseminwords']) ) {
            $errors['responseminwords']  = get_string('minwords_message_admin', 'qtype_essaymaxwords');                  
        } 
        
        if ($fromform['responseminwords'] && is_numeric($fromform['responseminwords']) && $fromform['responseminwords']<0) {
            $errors['responseminwords']  = get_string('minwords_positive_message_admin', 'qtype_essaymaxwords');                  
        }           

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['responseminwords'] && $fromform['responsemaxwords'] && is_numeric($fromform['responseminwords']) && $fromform['responseminwords']>0 && 
            is_numeric($fromform['responsemaxwords']) && $fromform['responsemaxwords']>0 && $fromform['responseminwords']>$fromform['responsemaxwords']) {
            $errors['responseminwords']  = get_string('words_wrong_message_admin', 'qtype_essaymaxwords');                  
        }         
                        

        return $errors;
    }

    public function qtype() {
        return 'essaymaxwords';
    }
}
