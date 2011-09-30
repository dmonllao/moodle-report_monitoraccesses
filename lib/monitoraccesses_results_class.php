<?php // $Id$

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
                $from = optional_param('from_'.$stripid, false, PARAM_INT);
                $to = optional_param('to_'.$stripid, false, PARAM_INT);

                // gmmktime because it's only to sum
                $fromtimestamp = gmmktime($from['from_'.$stripid.'_hours'], $from['from_'.$stripid.'_mins'], '00', '01', '01', '1970');
                $totimestamp = gmmktime($to['to_'.$stripid.'_hours'], $to['to_'.$stripid.'_mins'], '00', '01', '01', '1970');

                // Check the submitted dates
                if ($fromtimestamp >= $totimestamp) {
                    $errors[] = get_string("wrongdates", "report_monitoraccesses", $stripid);
                }

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

                    // mktime because we must add the user GMT
                    $datetimestamp = mktime('00', '00', '00', intval($date[3]), intval($date[4]), intval($date[2]));
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
            redirect($CFG->wwwroot.'/admin/report/monitoraccesses/index.php?action=selectstrips&amp;samesession=1', $errorstr, 10);
        }

        // If there are no selected strips redirect
        if (empty($SESSION->monitoraccessesreport->strips)) {
            redirect($CFG->wwwroot.'/admin/report/monitoraccesses/index.php?action=selectstrips&amp;samesession=1',
                     get_string("nostrips", "report_monitoraccesses"),
                     10);
        }

    }


    public function process() {

        global $CFG, $SESSION;

        // If there are no selected strips don't store
        if (empty($SESSION->monitoraccessesreport->strips)) {
            return false;
        }

        // Save selected strips and dates to DB
        $this->store_report();

        // Prepare data to select
        $selectedcourses = implode(',', $SESSION->monitoraccessesreport->courses);


        // Stores init and end timestamps
        $timestamps = array();
        foreach ($SESSION->monitoraccessesreport->strips as $strip) {

            if (!empty($strip->dates)) {
                foreach ($strip->dates as $date) {
                    $dateinit = $date + $strip->from;
                    $timestamps[$dateinit]->from = $dateinit;
                    $timestamps[$dateinit]->to = $date + $strip->to;
                }
            }
        }

        if (empty($timestamps)) {
            return false;
        }

        ksort($timestamps);

        // Looking for the first and the last selected strips
        $first = reset($timestamps);
        $last = end($timestamps);

        // One query per user searching only between the first and the last selected days
        if ($SESSION->monitoraccessesreport->users) {

            // Array to store the users connections between the selecet strips
            $accesses = array();

            foreach ($SESSION->monitoraccessesreport->users as $userid) {

                // Getting data ordered by time to improve the iteration time
                $sql = "SELECT l.time FROM {$CFG->prefix}log l
                        WHERE l.userid = '$userid'
                        AND l.module = 'user' AND l.action = 'login'
                        AND l.time > '{$first->from}' AND l.time < '{$last->to}'
                        ORDER BY l.time ASC";
                $logins = get_records_sql($sql);

                // Getting logout times
                $logouts = get_records_sql(str_replace('login', 'logout', $sql));

                // Really HEAVY iteration
                if ($logins) {
                    foreach ($logins as $log) {

                        unset($found);
                        unset($alreadyadded);
                        unset($nextlogout);
                        unset($setsessiontimeout);

                        // Iterate through the strips
                        // TODO: Try to put logout iteration outside the $logins iteration
                        $date = reset($timestamps);

                        do {

                            // If the login time is between the init and end timestamps
                            if ($log->time > $date->from && $log->time < $date->to) {

                                $found = 1;

                                // Get the next logout
                                if ($logouts) {
                                    foreach ($logouts as $logout) {

                                        // TODO: unset the passed logouts to improve iterations
                                        if ($logout->time > $log->time && empty($nextlogout)) {
                                            $nextlogout = $logout->time;
                                        }
                                    }
                                }

                                // We must check the already found connections to ensure that there aren't duplicate entries
                                if (!empty($accesses[$userid])) {
                                    foreach ($accesses[$userid] as $striptimes) {

                                        if ($log->time > $striptimes->login && $log->time < $striptimes->logout) {
                                            $alreadyadded = true;
                                        }
                                    }
                                }

                                // If it hasn't coincidences, add it
                                if (empty($alreadyadded)) {

                                    // Login time
                                    $accesses[$userid][$log->time]->login = $log->time;

                                    // Checking if there are logins between this login and the next logout
                                    // Useful to detect logins without logout and posterior login with logout
                                    if (!empty($nextlogout)) {
                                        foreach ($logins as $nextlogin) {
                                            if ($nextlogin->time > $log->time && $nextlogin->time < $nextlogout) {
                                                $setsessiontimeout = true;
                                                break;
                                            }
                                        }
                                    }

                                    $logday = mktime('00', '00', '00',
                                                        date('m', $log->time),
                                                        date('d', $log->time),
                                                        date('Y', $log->time));

                                    // If there are two consecutive logins without logout
                                    if (!empty($setsessiontimeout)) {
                                        $accesses[$userid][$log->time]->logout = $log->time + $CFG->sessiontimeout;

                                    // The next logout
                                    } else if (!empty($nextlogout) && $nextlogout < ($logday + DAYSECS)) {
                                        $accesses[$userid][$log->time]->logout = $nextlogout;

                                    // If there are no next logout take the sessiontimeout as logout
                                    } else {
                                        $accesses[$userid][$log->time]->logout = $log->time + $CFG->sessiontimeout;
                                    }

                                    // Limiting the logout to the strip end
                                    if ($accesses[$userid][$log->time]->logout > $date->to) {
                                        $accesses[$userid][$log->time]->logout = $date->to;
                                    }
                                }
                            }

                            // Getting the next strip
                            $date = next($timestamps);

                        } while (empty($found) && $date);
                    }
                }

            }

            $this->bus = $accesses;
        }

    }


    public function display() {

        global $CFG, $SESSION;

        parent::display();

        if (!$this->bus) {
            redirect($CFG->wwwroot.'/admin/report/monitoraccesses/index.php',
                     get_string('notaccessesfound', 'report_monitoraccesses'),
                     10);
        }

        foreach ($this->bus as $userid => $userdata) {
            $this->display_user($userid, $userdata);
        }
    }


    private function display_user($userid, $userdata) {

        global $CFG;

        $user = get_record('user', 'id', $userid);

        $outputheader = '<div id="user_'.$user->id.'" class="monitoraccesses_userresults">';

        // To hide/show
        $outputheader.= '<input type="image" src="'.$CFG->pixpath.'/t/switch_plus.gif" '.
                        'id="togglehide_'.$user->id.'" '.
                        'onclick="elementToggleHide(this, false, function (el) {return document.getElementById(\'dates_'.$user->id.'\');},'.
                        ' \''.get_string("show").'\', \''.get_string("hide").'\'); return false;" '.
                        'alt="'.get_string('show').'" title="'.get_string('show').'" class="hide-show-image monitoraccesses_button" />';

        $outputheader.= print_user_picture($user->id, '1', $user->picture, 0, true).' '.$user->firstname.' '.$user->lastname;


        // Print div with the user logins
        $totaltime = 0;
        $outputdates = '<div id="dates_'.$user->id.'" class="hidden">';

        $row = 0;
        $table->head = array(get_string("date"),
                             get_string("fromtime", "report_monitoraccesses"),
                             get_string("totime", "report_monitoraccesses"),
                             get_string("duration", "report_monitoraccesses"));
        $table->align = array('left', 'center', 'center', 'center');
        foreach ($userdata as $date) {

            $table->data[$row][0] = date('d-m-Y', $date->login);
            $table->data[$row][1] = date('H:i', $date->login);
            $table->data[$row][2] = date('H:i', $date->logout);
            $table->data[$row][3] = gmdate('H:i', $date->logout - $date->login);

            $totaltime = $totaltime + ($date->logout - $date->login);
            $row++;
        }

        $outputdates.= print_table($table, true);
        $outputdates.= '</div>';


        // Adding the total sum (format H:i, Nº jours)
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

        global $USER, $SESSION;

        // Report
        $ma->userid = $USER->id;
        $ma->timecreated = time();
        $ma->id = insert_record('monitoraccesses', $ma);

        // Courses
        $mac->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->courses as $courseid) {
            $mac->courseid = $courseid;
            if (!insert_record('monitoraccesses_course', $mac)) {
                debugging('Can\'t insert into monitoraccessesreport_course'.print_r($mac));
            }
        }

        // Users
        $mau->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->users as $userid) {
            $mau->userid = $userid;
            if (!insert_record('monitoraccesses_user', $mau)) {
                debugging('Can\'t insert into monitoraccessesreport_user'.print_r($mau));
            }
        }

        // Strips
        $mas->monitoraccessesid = $ma->id;
        foreach ($SESSION->monitoraccessesreport->strips as $strip) {

            if (!empty($strip->dates)) {
                $mas->beginseconds = $strip->from;
                $mas->endseconds = $strip->to;
                $mas->days = implode(',', $strip->dates);
                if (!insert_record('monitoraccesses_strip', $mas)) {
                    debugging('Can\'t insert into monitoraccessesreport_strip'.print_r($mas));
                }
            }
        }
    }
}

