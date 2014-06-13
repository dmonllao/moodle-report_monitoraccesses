<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/monitoraccesses/lib/monitoraccesses_courseslist_class.php');
require_once($CFG->dirroot . '/report/monitoraccesses/lib/monitoraccesses_userslist_class.php');
require_once($CFG->dirroot . '/report/monitoraccesses/lib/monitoraccesses_results_class.php');

/**
 * Unit tests for {@link report_monitoraccesses}
 *
 * @group report_monitoraccesses
 * @package report_monitoraccesses
 * @copyright 2014 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processes_test extends advanced_testcase {

    private static $fifthjune;
    private static $sixthjune;

    public function test_courses() {

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $courseslist = new testable_monitoraccesses_courseslist_class('courseslist');

        $courseslist->process();
        $this->assertEquals(0, count($courseslist->get_bus()));

        $generator->create_course();

        $courseslist->process();
        $this->assertEquals(1, count($courseslist->get_bus()));
    }

    public function test_users() {
        global $SESSION;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id);
        $generator->enrol_user($user2->id, $course->id);

        $SESSION->monitoraccessesreport->courses[$course->id] = $course->id;

        $userslist = new testable_monitoraccesses_userslist_class('userslist');

        $userslist->process();
        $this->assertEquals(2, count($userslist->get_bus()));
    }

    /**
     * Results running with a single big strip.
     *
     * @return void
     */
    public function test_single_strip_results() {
        global $SESSION, $CFG;

        // Fill logs table.
        list($courses, $users) = $this->set_up_results_test();

        // Here we need 1 user and 2 courses.
        $user = $users[0];
        $course1 = $courses[0];
        $course2 = $courses[1];

        $SESSION->monitoraccessesreport = new stdClass();

        // Set strips, from 09:00 to 16:00.
        $SESSION->monitoraccessesreport->strips[1] = new stdClass();
        $SESSION->monitoraccessesreport->strips[1]->from = 9 * HOURSECS;
        $SESSION->monitoraccessesreport->strips[1]->to = 16 * HOURSECS;

        // Only 5th June here.
        $SESSION->monitoraccessesreport->strips[1]->dates = array(self::$fifthjune);

        $SESSION->monitoraccessesreport->users[$user->id] = $user->id;

        // First case, only with one course.
        $SESSION->monitoraccessesreport->courses[$course1->id] = $course1->id;

        $results = new testable_monitoraccesses_results_class('results');

        // Results should match 1 course logs.
        $results->process();
        $processresults = $results->get_bus();
        $userresults = $processresults[$user->id];

        // 3 different ranges.
        $this->assertEquals(3, count($userresults));

        // The first range goes from 9am to 9:10am + the session timeout.
        $range = reset($userresults);
        $start = (9 * HOURSECS) + self::$fifthjune;
        $end = (9 * HOURSECS) + (10 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The second range goes from 9:50 to 10:00 + the session timeout.
        $range = next($userresults);
        $start = (9 * HOURSECS) + (50 * MINSECS) + self::$fifthjune;
        $end = (10 * HOURSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The third range goes from 15:00 to 15:05 + the session timeout.
        $range = next($userresults);
        $start = (15 * HOURSECS) + self::$fifthjune;
        $end = (15 * HOURSECS) + (5 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // Second case, now two courses.
        $SESSION->monitoraccessesreport->courses[$course2->id] = $course2->id;

        $results->process();
        $processresults = $results->get_bus();
        $userresults = $processresults[$user->id];

        $this->assertEquals(5, count($userresults));

        // The first range goes from 9am to 9:15am + the session timeout.
        $range = reset($userresults);
        $start = (9 * HOURSECS) + self::$fifthjune;
        $end = (9 * HOURSECS) + (15 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The second range goes from 09:50 to 10:00 + the session timeout.
        $range = next($userresults);
        $start = (9 * HOURSECS) + (50 * MINSECS) + self::$fifthjune;
        $end = (10 * HOURSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The third range goes from 11:00 to 11:05 + the session timeout.
        $range = next($userresults);
        $start = (11 * HOURSECS) + self::$fifthjune;
        $end = (11 * HOURSECS) + (5 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The forth range goes from 12:05 to the session timeout.
        $range = next($userresults);
        $start = (12 * HOURSECS) + (5 * MINSECS) + self::$fifthjune;
        $end = $start + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The fifth range goes from 15:00 to 15:05 + the session timeout.
        $range = next($userresults);
        $start = (15 * HOURSECS) + self::$fifthjune;
        $end = (15 * HOURSECS) + (5 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);
    }

    /**
     * Results using multiple strips.
     *
     * @return void
     */
    public function test_multiple_strips_results() {
        global $SESSION, $CFG;

        // Fill logs table.
        list($courses, $users) = $this->set_up_results_test();

        // Here we need 1 user and 2 courses.
        $user = $users[0];
        $course1 = $courses[0];
        $course2 = $courses[1];

        $SESSION->monitoraccessesreport = new stdClass();

        $SESSION->monitoraccessesreport->users[$user->id] = $user->id;
        $SESSION->monitoraccessesreport->courses[$course1->id] = $course1->id;
        $SESSION->monitoraccessesreport->courses[$course2->id] = $course2->id;

        // First we try with many strips.

        // From 09:00 to 13:00, fifth and sixth of June.
        $SESSION->monitoraccessesreport->strips[1] = new stdClass();
        $SESSION->monitoraccessesreport->strips[1]->from = 9 * HOURSECS;
        $SESSION->monitoraccessesreport->strips[1]->to = 13 * HOURSECS;
        $SESSION->monitoraccessesreport->strips[1]->dates = array(self::$fifthjune, self::$sixthjune);

        // From 15:00 to 16:00, fifth and sixth of June (there are no records the 6th).
        $SESSION->monitoraccessesreport->strips[2] = new stdClass();
        $SESSION->monitoraccessesreport->strips[2]->from = 15 * HOURSECS;
        $SESSION->monitoraccessesreport->strips[2]->to = 16 * HOURSECS;
        $SESSION->monitoraccessesreport->strips[2]->dates = array(self::$fifthjune, self::$sixthjune);

        // Run the report.
        $results = new testable_monitoraccesses_results_class('results');
        $results->process();
        $processresults = $results->get_bus();
        $userresults = $processresults[$user->id];

        // 8 different ranges.
        $this->assertEquals(8, count($userresults));

        // The first range goes from 9am to 9:15am + the session timeout.
        $range = reset($userresults);
        $start = (9 * HOURSECS) + self::$fifthjune;
        $end = (9 * HOURSECS) + (15 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The second range goes from 09:50 to 10:00 + the session timeout.
        $range = next($userresults);
        $start = (9 * HOURSECS) + (50 * MINSECS) + self::$fifthjune;
        $end = (10 * HOURSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The third range goes from 11:00 to 11:05 + the session timeout.
        $range = next($userresults);
        $start = (11 * HOURSECS) + self::$fifthjune;
        $end = (11 * HOURSECS) + (5 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The forth range goes from 12:05 to the session timeout.
        $range = next($userresults);
        $start = (12 * HOURSECS) + (5 * MINSECS) + self::$fifthjune;
        $end = $start + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The fifth range goes from 15:00 to 15:05 + the session timeout.
        $range = next($userresults);
        $start = (15 * HOURSECS) + self::$fifthjune;
        $end = (15 * HOURSECS) + (5 * MINSECS) + self::$fifthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The sixth range goes from 09:00am to 09:10am + the session timeout.
        $range = next($userresults);
        $start = (9 * HOURSECS) + self::$sixthjune;
        $end = (9 * HOURSECS) + (10 * MINSECS) + self::$sixthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The seventh range goes from 09:50am to 10:00am + the session timeout.
        $range = next($userresults);
        $start = (9 * HOURSECS) + (50 * MINSECS) + self::$sixthjune;
        $end = (10 * HOURSECS) + self::$sixthjune + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

        // The eighth range goes from 12:00am to the session timeout.
        $range = next($userresults);
        $start = (12 * HOURSECS) + self::$sixthjune;
        $end = $start + $CFG->sessiontimeout;
        $this->assertEquals($start, $range->firstlog);
        $this->assertEquals($end, $range->lastlog);

    }

    /**
     * Common results tests set up.
     *
     * @return array courses and users
     */
    protected function set_up_results_test() {

        global $DB;

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        // test dates using GMT.
        self::$fifthjune = gmmktime('00', '00', '00', '06', '05', '2014');
        self::$sixthjune = gmmktime('00', '00', '00', '06', '06', '2014');

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $user1 = $generator->create_user();
        $generator->enrol_user($user1->id, $course1->id);
        $generator->enrol_user($user1->id, $course2->id);

        // Sets timeout to 15 minutes.
        set_config('sessiontimeout', 900);

        // Fill database logs.

        // Includes logs from:
        // 5 June course1
        // 09:00 - 09:10 - 09:50 - 10:00 - 15:00 - 15:05
        // 5 June course2
        // 09:00 - 09:15 - 09:55 - 11:00 - 11:05 - 12:05
        // 6 June course
        // 09:00 - 09:10 - 09:50 - 10:00 - 12:00

        $files = array('log' => __DIR__ . '/fixtures/results_data.csv');
        $this->loadDataSet($this->createCsvDataSet($files));

        // Update DB template ids to the created user and course ids.
        $updatesql = 'UPDATE {log} SET userid = ? WHERE userid = ?';
        $DB->execute($updatesql, array($user1->id, '99999'));

        $updatesql = 'UPDATE {log} SET course = ? WHERE course = ?';
        $DB->execute($updatesql, array($course1->id, '99999'));

        $updatesql = 'UPDATE {log} SET course = ? WHERE course = ?';
        $DB->execute($updatesql, array($course2->id, '88888'));

        return array(array($course1, $course2), array($user1));
    }
}


// Testable interfaces /////////////////

class testable_monitoraccesses_courseslist_class extends monitoraccesses_courseslist_class {
    public function get_bus() {
        return $this->bus;
    }
}

class testable_monitoraccesses_userslist_class extends monitoraccesses_userslist_class {
    public function get_bus() {
        return $this->bus;
    }
}

class testable_monitoraccesses_results_class extends monitoraccesses_results_class {
    public function get_bus() {
        return $this->bus;
    }
}
