<?php // $Id$

class monitoraccesses_courseslist_form_class extends monitoraccesses_form_class {


    public function definition() {

        $this->_form->addElement('header', 'header_courses', get_string('selectcoursesheader', 'report_monitoraccesses'));

        // One checkbox for one course in $this->results
        $this->add_checkbox_list('course');

        // Submit button
        $submitjs = 'display_users();return false;';
        $submitattrs = array('onclick' => $submitjs,
                             'onsubmit' => $submitjs);
        $this->_form->addElement('submit', 'submitcourses', get_string('showusers', 'report_monitoraccesses'), $submitattrs);

    }
}

?>