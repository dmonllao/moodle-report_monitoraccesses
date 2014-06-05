<?php // $Id$

class monitoraccesses_courseslist_class extends monitoraccesses_class {


    public function controller() {

        global $CFG, $SESSION, $DB;

        // Load last report
        if (empty($SESSION->monitoraccessesreport)) {

            $last = $DB->get_record_sql("SELECT id FROM {$CFG->prefix}monitoraccesses WHERE timecreated =
                                    (SELECT max(timecreated) FROM {$CFG->prefix}monitoraccesses)");

            if ($last) {

                $coursessql = "SELECT courseid FROM {$CFG->prefix}monitoraccesses_course WHERE monitoraccessesid = '$last->id'";
                $userssql = "SELECT mu.userid FROM {$CFG->prefix}monitoraccesses_user mu
                             JOIN {$CFG->prefix}user u ON u.id = mu.userid
                             WHERE monitoraccessesid = '$last->id'
                             ORDER BY u.lastname ASC";
                $stripssql = "SELECT * FROM {$CFG->prefix}monitoraccesses_strip WHERE monitoraccessesid = '$last->id'
                              ORDER BY id ASC";

                $SESSION->monitoraccessesreport = new stdClass();
                $SESSION->monitoraccessesreport->courses = $DB->get_records_sql($coursessql);
                $SESSION->monitoraccessesreport->users = $DB->get_records_sql($userssql);

                $strips = $DB->get_records_sql($stripssql);
                if ($strips) {
                    $i = 1;
                    foreach ($strips as $strip) {
                        $SESSION->monitoraccessesreport->strips[$i] = new stdClass();
                        $SESSION->monitoraccessesreport->strips[$i]->from = $strip->beginseconds;
                        $SESSION->monitoraccessesreport->strips[$i]->to = $strip->endseconds;

                        $stripdates = array_combine(explode(',', $strip->days), explode(',', $strip->days));
                        ksort($stripdates);
                        $SESSION->monitoraccessesreport->strips[$i]->dates = $stripdates;
                        $i++;
                    }
                }

            }
        }
    }


    public function process() {

        global $CFG, $DB;

        $sql = "SELECT id, fullname as value
                FROM {$CFG->prefix}course
                WHERE id != 1
                ORDER BY sortorder ASC";

        $this->bus = $DB->get_records_sql($sql);
    }


    public function display() {

        parent::display();

        echo '<div id="id_users"></div>';
    }
}

