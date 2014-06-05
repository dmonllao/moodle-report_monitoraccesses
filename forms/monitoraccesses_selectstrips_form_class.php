<?php

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot . '/report/monitoraccesses/forms/monitoraccesses_form_class.php');

class monitoraccesses_selectstrips_form_class extends monitoraccesses_form_class {


    public function definition() {

        global $CFG, $SESSION, $OUTPUT;

        $nstrips = 3;

        // Hours and minuts select options
        $hours = $this->get_hours();
        $mins = $this->get_mins();

        // Dates to display months from
        $initialmonth = date('m') + 1;
        $initialyear = date('Y') - 1;

        // Printing the dates header
        $this->_form->addElement('header', 'header_strips', get_string('selectstripsheader', 'report_monitoraccesses'));

        for ($stripid = 1; $stripid <= $nstrips; $stripid++) {

            // Javascript to toggle the strips
            $togglejs = 'elementToggleHide(this,
                                           false,
                                           function (el) {return document.getElementById(\'dates_'.$stripid.'\');},
                                           '.' \''.get_string("show").'\',
                                           \''.get_string("hide").'\');';

            // Show or hide by default
            $class = '';
            $stripchecked = 'checked="checked"';
            $hideshowimage = $OUTPUT->pix_url('t/switch_minus');
            $hideshowalt = get_string('hide');
            if (empty($SESSION->monitoraccessesreport->strips[$stripid])) {
                $class = 'hidden';
                $stripchecked = '';
                $hideshowimage = $OUTPUT->pix_url('t/switch_plus');
                $hideshowalt = get_string('show');
            }

            $this->_form->addElement('html', '<div class="monitoraccesses_dates">');


            // To hide/show
            $hideshowdiv = '<div class="monitoraccesses_stripcommandshowhide">
                            <input type="image" src="'.$hideshowimage.'" '.
                            'id="togglehide_'.$stripid.'" '.
                            'onclick="'.$togglejs.'return false;" '.
                            'alt="'.$hideshowalt.'" title="'.$hideshowalt.'" class="hide-show-image monitoraccesses_button" />
                            </div>';
            $this->_form->addElement('html', $hideshowdiv);


            // To enable the strip
            $enablediv = '<div class="monitoraccesses_stripcommandenable monitoraccesses_button">
                          '.get_string("stripenabled", "report_monitoraccesses").' <input type="checkbox" name="strip_'.$stripid.'_enabled" '.$stripchecked.'/>
                          </div>';
            $this->_form->addElement('html', $enablediv);


            // Big strip div
            $this->_form->addElement('html', '<div id="dates_'.$stripid.'" class="'.$class.'">');

            // Each strip has it's own from and to selectors
            $selects = array('from', 'to');
            foreach ($selects as $select) {

                $hoursname = $select.'_'.$stripid.'_hours';
                $minsname = $select.'_'.$stripid.'_mins';

                // From
                $striparray = array();
                $striparray[] = $this->_form->createElement('select', $hoursname, '', $hours);
                $striparray[] = $this->_form->createElement('select', $minsname, '', $mins);

                // Strip ini (identified by his strip number)
                $this->_form->addGroup($striparray,
                                       $select.'_'.$stripid,
                                       get_string($select.'time', 'report_monitoraccesses').': ');

                // Check stored values
                if (!empty($SESSION->monitoraccessesreport->strips[$stripid]->$select)) {

                    $values = array();
                    $values[$hoursname] = gmdate('H', $SESSION->monitoraccessesreport->strips[$stripid]->$select);
                    $values[$minsname] = gmdate('i', $SESSION->monitoraccessesreport->strips[$stripid]->$select);
                    $this->_form->setDefault($select.'_'.$stripid, $values);
                }
            }

            $this->_form->addElement('html', '<br/><br/><table><tr>');

            // 12 months from this month
            $year = $initialyear;
            for ($n = 0; $n < 12; $n++) {
                $month = $initialmonth + $n;
                if ($month > 12) {
                    $month = $month - 12;
                    $year = $initialyear + 1;
                }

                // Every 3 months
                if ($n % 3 == 0) {
                    $this->_form->addElement('html', '</tr><tr>');
                }

                $calendarhtml = userdate(mktime('00', '00', '00', $month, '15', $year), get_string('strftimemonthyear'));
                $calendarhtml.= ' <input type="checkbox"
                                  onclick="toggle_month(this, \''.$stripid.'\', \''.$year.'\', \''.$month.'\');"
                                  title="'.get_string("checkall", "report_monitoraccesses").'" />';
                $calendarhtml.= $this->calendar_get_mini($stripid, $month, $year, $stripchecked);
                $this->_form->addElement('html', '<td class="monitoraccesses_calendar">'.$calendarhtml.'</td>');
            }

            $this->_form->addElement('html', '</tr></table>');

            // Closing that iteration div
            $this->_form->addElement('html', '<br/></div></div>');
        }

        // Submit button
        $this->_form->addElement('submit', 'submitstrips', get_string('showresults', 'report_monitoraccesses'));

        $this->_form->addElement('hidden', 'action', 'results');
    }


    private function get_hours() {

        $hours = array();
        for ($i = 0; $i < 24; $i++) {

            $hours[$i] = $i;
            if ($i < 10) {
                $hours[$i] = '0'.$hours[$i];
            }
        }

        return $hours;
    }


    private function get_mins() {

        $mins = array();
        for ($i = 0; $i < 60; $i = $i + 5) {

            $mins[$i] = $i;
            if ($i < 10) {
                $mins[$i] = '0'.$mins[$i];
            }
        }

        return $mins;
    }


    private function calendar_get_mini($id, $cal_month, $cal_year, $checkedbydefault = false) {

        global $CFG, $USER, $SESSION;

        $display = new stdClass;
        $display->minwday = get_user_preferences('calendar_startwday', CALENDAR_STARTING_WEEKDAY);
        $display->maxwday = $display->minwday + 6;

        $content = '';

        if(!empty($cal_month) && !empty($cal_year)) {
            $thisdate = usergetdate(time()); // Date and time the user sees at his location
            if($cal_month == $thisdate['mon'] && $cal_year == $thisdate['year']) {
                // Navigated to this month
                $date = $thisdate;
                $display->thismonth = true;
            } else {
                // Navigated to other month, let's do a nice trick and save us a lot of work...
                if(!checkdate($cal_month, 1, $cal_year)) {
                    $date = array('mday' => 1, 'mon' => $thisdate['mon'], 'year' => $thisdate['year']);
                    $display->thismonth = true;
                }
                else {
                    $date = array('mday' => 1, 'mon' => $cal_month, 'year' => $cal_year);
                    $display->thismonth = false;
                }
            }
        }

        // Fill in the variables we 're going to use, nice and tidy
        list($d, $m, $y) = array($date['mday'], $date['mon'], $date['year']); // This is what we want to display
        $display->maxdays = calendar_days_in_month($m, $y);

        if (get_user_timezone_offset() < 99) {
            // We 'll keep these values as GMT here, and offset them when the time comes to query the db
            $display->tstart = gmmktime(0, 0, 0, $m, 1, $y); // This is GMT
            $display->tend = gmmktime(23, 59, 59, $m, $display->maxdays, $y); // GMT
        } else {
            // no timezone info specified
            $display->tstart = mktime(0, 0, 0, $m, 1, $y);
            $display->tend = mktime(23, 59, 59, $m, $display->maxdays, $y);
        }

        $startwday = dayofweek(1, $m, $y);

        // Align the starting weekday to fall in our display range
        // This is simple, not foolproof.
        if($startwday < $display->minwday) {
            $startwday += 7;
        }

        //Accessibility: added summary and <abbr> elements.
        ///global $CALENDARDAYS; appears to be broken.
        $days_title = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');

        $summary = get_string('calendarheading', 'calendar', userdate(make_timestamp($y, $m), get_string('strftimemonthyear')));
        $summary = get_string('tabledata', 'access', $summary);
        $content .= '<table class="minicalendar" summary="'.$summary.'">'; // Begin table
        $content .= '<tr class="weekdays">'; // Header row: day names

        // Print out the names of the weekdays
        $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
        for($i = $display->minwday; $i <= $display->maxwday; ++$i) {
            // This uses the % operator to get the correct weekday no matter what shift we have
            // applied to the $display->minwday : $display->maxwday range from the default 0 : 6
            $content .= '<th scope="col"><abbr title="'. get_string($days_title[$i % 7], 'calendar') .'">'.
                get_string($days[$i % 7], 'calendar') ."</abbr></th>\n";
        }

        $content .= '</tr><tr>'; // End of day names; prepare for day numbers

        // For the table display. $week is the row; $dayweek is the column.
        $dayweek = $startwday;

        // Paddding (the first week may have blank days in the beginning)
        for($i = $display->minwday; $i < $startwday; ++$i) {
            $content .= '<td class="dayblank">&nbsp;</td>'."\n";
        }

        // Now display all the calendar
        for ($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
            if ($dayweek > $display->maxwday) {
                // We need to change week (table row)
                $content .= '</tr><tr>';
                $dayweek = $display->minwday;
            }



            // Weekend or not
            $cell = '';
            if (CALENDAR_WEEKEND & (1 << ($dayweek % 7))) {
                $class = 'weekend day';
            } else {
                $class = 'day';
            }

            // Checked or not
            $checked = '';
            $elementmktime = mktime('00', '00', '00', $cal_month, $day, $cal_year);
            if ($checkedbydefault && empty($SESSION->monitoraccessesreport->strips[$id])) {
                $checked = 'checked="checked"';
            } else if (!empty($SESSION->monitoraccessesreport->strips[$id]->dates[$elementmktime])) {
                $checked = 'checked="checked"';
            }


            // Adding the day number and the checkbox
            $elementname = 'date_'.$id.'_'.$cal_year.'_'.$cal_month.'_'.$day;
            $cell = $day.'<input type="checkbox" name="'.$elementname.'" '.$checked.'/>';

            // Just display it
            if(!empty($class)) {
                $class = ' class="'.$class.'"';
            }
            $content .= '<td'.$class.'>'.$cell."</td>\n";
        }

        // Paddding (the last week may have blank days at the end)
        for($i = $dayweek; $i <= $display->maxwday; ++$i) {
            $content .= '<td class="dayblank">&nbsp;</td>';
        }
        $content .= '</tr>'; // Last row ends

        $content .= '</table>'; // Tabular display of days ends

        return $content;
    }

}

