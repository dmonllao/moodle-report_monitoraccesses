<?php

require_once($CFG->dirroot . '/report/monitoraccesses/lib/monitoraccesses_class.php');

class monitoraccesses_results_class extends monitoraccesses_class {


    public function controller() {

        global $CFG, $SESSION;

        $nstrips = 3;
        $errors = array();

        // Unset previously stored last report
        unset($SESSION->monitoraccessesreport->strips);

        // Look for enabled strips
        for ($stripid = 1; $stripid <= $nstrips; $stripid++) {
            if (optional_param('strip_'.$stripid.'_enabled', false, PARAM_BOOL)) {

                $fromname = 'from_'.$stripid;
                $toname = 'to_'.$stripid;
                $from = optional_param_array('from_'.$stripid, false, PARAM_INT);
                $to = optional_param_array('to_'.$stripid, false, PARAM_INT);

                // gmmktime because it's only to sum
                $fromtimestamp = gmmktime($from['from_'.$stripid.'_hours'], $from['from_'.$stripid.'_mins'], '00', '01', '01', '1970');
                $totimestamp = gmmktime($to['to_'.$stripid.'_hours'], $to['to_'.$stripid.'_mins'], '00', '01', '01', '1970');

                // Check the submitted dates
                if ($fromtimestamp >= $totimestamp) {
                    $errors[] = get_string("wrongdates", "report_monitoraccesses", $stripid);
                }

                $SESSION->monitoraccessesreport->strips[$stripid] = new stdClass();
                $SESSION->monitoraccessesreport->strips[$stripid]->from = $fromtimestamp;
                $SESSION->monitoraccessesreport->strips[$stripid]->to = $totimestamp;
            }
        }

        // Getting the strips selected days
        foreach ($_POST as $key => $value) {
            if (strstr($key, 'date_') != false) {

                // date[1] == stripid, date[2] == year, date[3] == month, date[4] == day
                $date = explode('_', $key);

                // Only store it if the strip it's enabled
                if (!empty($SESSION->monitoraccessesreport->strips[$date[1]])) {

                    // Using the user timezone.
                    $datetimestamp = usertime(gmmktime('00', '00', '00', intval($date[3]), intval($date[4]), intval($date[2])));
                    $SESSION->monitoraccessesreport->strips[$date[1]]->dates[$datetimestamp] = $datetimestamp;
                }
            }
        }

        // Sorting the strips by date to iterate
        if (!empty($SESSION->monitoraccessesreport->strips)) {
            for ($i = 0; $i < count($SESSION->monitoraccessesreport->strips); $i++) {
                if (!empty($SESSION->monitoraccessesreport->strips[$i]->dates)) {
                    ksort($SESSION->monitoraccessesreport->strips[$i]->dates);

                // If there are no selected dates unset the strip
                } else {
                    unset($SESSION->monitoraccessesreport->strips[$i]);
                }
            }
        }

        // Redirect to the strips selector if there are problems
        if ($errors) {
            $errorstr = ucfirst(implode(' '.get_string("and", "report_monitoraccesses").' ', $errors));
            redirect($CFG->wwwroot.'/report/monitoraccesses/index.php?action=selectstrips&amp;samesession=1', $errorstr, 10);
        }

        // If there are no selected strips redirect
        if (empty($SESSION->monitoraccessesreport->strips)) {
            redirect($CFG->wwwroot.'/report/monitoraccesses/index.php?action=selectstrips&amp;samesession=1',
                     get_string("nostrips", "report_monitoraccesses"),
                     10);
        }

    }


    public function process() {

        global $CFG, $SESSION, $DB;

        // If there are no selected strips don't store
        if (empty($SESSION->monitoraccessesreport->strips)) {
            return false;
        }

        // Save selected strips and dates to DB
        $this->store_report();

        // Prepare data to select
        $selectedcourses = implode(',', $SESSION->monitoraccessesreport->courses);

        // Stores init and end timestamps
        $selectedranges = array();
        foreach ($SESSION->monitoraccessesreport->strips as $strip) {

            if (!empty($strip->dates)) {
                foreach ($strip->dates as $date) {
                    $dateinit = $date + $strip->from;
                    $selectedranges[$dateinit] = new stdClass();
                    $selectedranges[$dateinit]->from = $dateinit;
                    $selectedranges[$dateinit]->to = $date + $strip->to;
                }
            }
        }

        if (empty($selectedranges)) {
            return false;
        }

        ksort($selectedranges);

        // Looking for the first and the last selected strips, hopefully
        // users will mostly choose just a few days.
        $first = reset($selectedranges);
        $last = end($selectedranges);

        // One query per user searching only between the first and the last selected days.
        if ($SESSION->monitoraccessesreport->users) {

            // Array to store the users connections between the selected strips
            $accesses = array();

            foreach ($SESSION->monitoraccessesreport->users as $userid) {

                $userlogs = array();

                // Getting user actions ordered by time to improve the iteration time.
                $sql = "SELECT l.id, l.time FROM {$CFG->prefix}log l
                        WHERE l.userid = '$userid' AND l.course IN ($selectedcourses)
                        AND l.time >= '{$first->from}' AND l.time <= '{$last->to}'
                        ORDER BY l.time ASC";
                if (!$logs = $DB->get_records_sql($sql)) {
                    continue;
                }

                // Iterate through the strips
                $date = reset($selectedranges);
                do {

                    // Really heavy iteration, better to force PHP to work than the database.
                    foreach ($logs as $log) {

                        // If it is inside a requested time range (this is the start & end dates + each
                        // of the selected days in the calendar, so quite a lot).
                        if ($log->time >= $date->from && $log->time <= $date->to) {

                            // Yes, we add the restriction of 1 action per second, but
                            // this tool purpose is a usage follow-up so it does not
                            // matter if they were 2 action in the same second.
                            $userlogs[$log->time] = $log;
                        }
                    }

                    // Getting the next strip.
                    $date = next($selectedranges);

                } while ($date);

                // Splitting the user logs in different accesses to the course.
                // We base this "split" in the system timeout value.
                // They are already ordered by time.
                unset($rangefirstlog);
                unset($rangelastlog);
                foreach ($userlogs as $time => $userlog) {

                    // If since the range last log passed more than TIMEOUT
                    // seconds we will start a new range.
                    if (!empty($rangelastlog) && ($rangelastlog + $CFG->sessiontimeout) < $time) {
                        unset($rangefirstlog);
                        unset($rangelastlog);
                    }

                    // A new set of logs.
                    if (empty($rangefirstlog)) {
                        $rangefirstlog = $time;
                        $accesses[$userid][$rangefirstlog] = new stdClass();
                        $accesses[$userid][$rangefirstlog]->firstlog = $time;
                    }

                    $accesses[$userid][$rangefirstlog]->lastlog = $time + $CFG->sessiontimeout;

                    // Keep track of the last log inside this range.
                    $rangelastlog = $time;
                }
            }

            $this->bus = $accesses;
        }

    }


    public function display() {

        global $CFG, $SESSION, $PAGE, $OUTPUT;

        parent::display();

        if (!$this->bus) {
            redirect($CFG->wwwroot.'/report/monitoraccesses/index.php',
                     get_string('notaccessesfound', 'report_monitoraccesses'),
                     10);
        }

        $minusicon = $OUTPUT->pix_url('t/switch_minus');
        $plusicon = $OUTPUT->pix_url('t/switch_plus');
        $PAGE->requires->yui_module(
            'moodle-report_monitoraccesses-toggle',
            'M.report_monitoraccesses.init_toggle',
            array(array('minusicon' => $minusicon->out(), 'plusicon' => $plusicon->out()))
        );
        $PAGE->requires->strings_for_js(
            array('show', 'hide'), 'moodle');

        foreach ($this->bus as $userid => $userdata) {
            $this->display_user($userid, $userdata);
        }
    }


    private function display_user($userid, $userdata) {

        global $CFG, $DB, $OUTPUT;

        $user = $DB->get_record('user', array('id' => $userid));

        $outputheader = '<div id="user_'.$user->id.'" class="monitoraccesses_userresults">';

        // To hide/show
        $plusicon = $OUTPUT->pix_url('t/switch_plus');
        $outputheader.= '<input type="image" src="' . $plusicon . '" '.
                        'id="togglehide_'.$user->id.'" '.
                        ' \''.get_string("show").'\', \''.get_string("hide").'\'); return false;" '.
                        'alt="'.get_string('show').'" title="'.get_string('show').'" class="hide-show-image monitoraccesses_button" />';

        $outputheader.= $OUTPUT->user_picture($user).' '.$user->firstname.' '.$user->lastname;


        // Print div with the user connections (previously splitted in process()).
        $totaltime = 0;
        $outputdates = '<div id="togglecontents_'.$user->id.'" class="hidden">';

        $row = 0;
        $table = new html_table();
        $table->head = array(get_string("date"),
                             get_string("fromtime", "report_monitoraccesses"),
                             get_string("totime", "report_monitoraccesses"),
                             get_string("duration", "report_monitoraccesses"));
        $table->align = array('left', 'center', 'center', 'center');
        foreach ($userdata as $date) {

            $firstlog = usergetdate($date->firstlog);
            $lastlog = usergetdate($date->lastlog);
            $table->data[$row][0] = date('d-m-Y', $date->firstlog);
            $table->data[$row][1] = str_pad($firstlog['hours'], 2, 0, STR_PAD_LEFT) . ':' .
                str_pad($firstlog['minutes'], 2, 0, STR_PAD_LEFT);
            $table->data[$row][2] = str_pad($lastlog['hours'], 2, 0, STR_PAD_LEFT) . ':' .
                str_pad($lastlog['minutes'], 2, 0, STR_PAD_LEFT);
            $table->data[$row][3] = gmdate('H:i', $date->lastlog - $date->firstlog);

            $totaltime = $totaltime + ($date->lastlog - $date->firstlog);
            $row++;
        }

        $outputdates.= html_writer::table($table);
        $outputdates.= '</div>';


        // Adding the total sum (format H:i, number of days)
        $dateformat = 'H:i';
        $daysstr = '';
        $daysoriginal = get_string("daydays", "report_monitoraccesses");
        $andstr = '';
        $andoriginal = get_string("and", "report_monitoraccesses");
        if ($totaltime > DAYSECS) {
            for ($i = 0; $i < strlen($daysoriginal); $i++) {
                $daysstr .= '\\'.$daysoriginal[$i];
            }
            for ($i = 0; $i < strlen($andoriginal); $i++) {
                $andstr .= '\\'.$andoriginal[$i];
            }
            $dateformat = 'z '.$daysstr.' '.$andstr.' '.$dateformat;
        }
        $outputheader .= ', '.get_string("logintime", "report_monitoraccesses").': '.gmdate($dateformat, $totaltime).' '.get_string("hours");

        echo $outputheader.$outputdates.'</div>';
    }


    private function store_report() {

        global $USER, $SESSION, $DB;

        // Report
        $ma = new stdClass();
        $ma->userid = $USER->id;
        $ma->timecreated = time();
        $ma->id = $DB->insert_record('monitoraccesses', $ma);

        // Courses
        $mac = new stdClass();
        $mac->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->courses as $courseid) {
            $mac->courseid = $courseid;
            if (!$DB->insert_record('monitoraccesses_course', $mac)) {
                debugging('Can\'t insert into monitoraccessesreport_course'.print_r($mac));
            }
        }

        // Users
        $mau = new stdClass();
        $mau->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->users as $userid) {
            $mau->userid = $userid;
            if (!$DB->insert_record('monitoraccesses_user', $mau)) {
                debugging('Can\'t insert into monitoraccessesreport_user'.print_r($mau));
            }
        }

        // Strips
        $mas = new stdClass();
        $mas->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->strips as $strip) {

            if (!empty($strip->dates)) {
                $mas->beginseconds = $strip->from;
                $mas->endseconds = $strip->to;
                $mas->days = implode(',', $strip->dates);
                if (!$DB->insert_record('monitoraccesses_strip', $mas)) {
                    debugging('Can\'t insert into monitoraccessesreport_strip'.print_r($mas));
                }
            }
        }
    }
}

