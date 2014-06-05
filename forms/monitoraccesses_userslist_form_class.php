<?php

require_once($CFG->dirroot . '/report/monitoraccesses/forms/monitoraccesses_form_class.php');

class monitoraccesses_userslist_form_class extends monitoraccesses_form_class {

    public function definition() {

        $this->_form->addElement('header', 'header_users', get_string('selectusersheader', 'report_monitoraccesses'));

        // One checkbox for one course in $this->results
        $this->add_checkbox_list('user');

        // Submit button
        $this->_form->addElement('submit', 'submitusers', get_string('selectstrips', 'report_monitoraccesses'));

        $this->_form->addElement('hidden', 'action', 'selectstrips');
        $this->_form->setType('action', PARAM_ALPHA);
    }
}

