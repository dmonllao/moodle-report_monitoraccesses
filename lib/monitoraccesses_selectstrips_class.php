<?php

require_once($CFG->dirroot . '/report/monitoraccesses/lib/monitoraccesses_class.php');

class monitoraccesses_selectstrips_class extends monitoraccesses_class {

    public function controller() {

        global $SESSION;


        if (!optional_param('samesession', false, PARAM_INT)) {
            unset($SESSION->monitoraccessesreport->users);
        }


        // Getting the selected users
        foreach ($_POST as $key => $var) {

            if (strstr($key, 'user_') != false) {

                // course = array('course', $courseid)
                $user = explode('_', $key);
                $userid = intval($user[1]);

                $SESSION->monitoraccessesreport->users[$userid] = $userid;
            }
        }
    }

}

