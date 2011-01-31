<?php // $Id$

class monitoraccesses_userslist_class extends monitoraccesses_class {


    public function controller() {

        global $SESSION;

        // Getting the selected courses
        unset($SESSION->monitoraccessesreport->courses);
        foreach ($_POST as $key => $var) {

            if (strstr($key, 'course_') != false) {

                // course = array('course', $courseid)
                $course = explode('_', $key);
                $courseid = intval($course[1]);

                $SESSION->monitoraccessesreport->courses[$courseid] = $courseid;
            }
        }

    }


    public function process() {

        global $CFG, $SESSION;

        // TODO: settings.php
        //$userids = '5';

        // Getting courses users
        $selectedcourses = implode(',', $SESSION->monitoraccessesreport->courses);
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.picture
                FROM {$CFG->prefix}context ctx
                JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id
                JOIN {$CFG->prefix}user u ON u.id = ra.userid
                WHERE ctx.contextlevel = '50' AND ctx.instanceid IN (".$selectedcourses.")
                ORDER BY u.lastname ASC";

        $data = get_records_sql($sql);

        // The user value must show the picture
        if ($data) {
            foreach ($data as $key => $user) {

                $div = '<div class="monitoraccesses_user">';
                $div.= $user->firstname.' '.$user->lastname.' '.print_user_picture($user->id, '1', $user->picture, 0, true);
                $div.= '</div>';

                $data[$key]->value = $div;
            }
        }

        $this->bus = $data;
    }


    /**
     * Created only to avoid the AJAX type display
     */
    public function display_footer() {}

}

