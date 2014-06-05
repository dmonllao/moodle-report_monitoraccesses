<?php

require_once($CFG->dirroot . '/report/monitoraccesses/forms/monitoraccesses_form_class.php');

class monitoraccesses_courseslist_form_class extends monitoraccesses_form_class {

    public function definition() {
        global $PAGE;

        $this->_form->addElement('header', 'header_courses', get_string('selectcoursesheader', 'report_monitoraccesses'));

        // One checkbox for one course in $this->results
        $this->add_checkbox_list('course');

        // The required JS.
        $PAGE->requires->yui_module('moodle-report_monitoraccesses-courseslist', 'M.report_monitoraccesses.init_courseslist');

        // Submit button
        $this->_form->addElement('submit', 'submitcourses', get_string('showusers', 'report_monitoraccesses'));
    }
}

