<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Show the attendance dates from a course, and show students that has attended on selected date.
 * Also can edit the attendance of a student from a date that have been selected.
 *
 * @package    SAMIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ .'/../../config.php');

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ .'/lib.php');
global $CFG, $DB, $PAGE, $OUTPUT, $COURSE;

$PAGE->requires->js_call_amd('block_samieattendance/samieattendance', 'init', array($USER->id));
$context = context_system::instance();
require_login();

$courseid = required_param('courseid', PARAM_INT);
$date = optional_param('date', null, PARAM_TEXT);
$userid = optional_param('userid', null, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));

$PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', array('id' => $courseid)));
$PAGE->navbar->add(get_string('courseattendances', 'block_samieattendance'),
        new moodle_url($CFG->wwwroot.'/blocks/samieattendance/showattendances.php?courseid='.$courseid));
if (isset($date)) {
    $PAGE->navbar->add(get_string('attendanceson', 'block_samieattendance').'&nbsp;'.
            block_samieattendance_format_human_timedate($date), '');
} else if (isset($userid)) {
    $fullname = optional_param('fullname', $userid, PARAM_TEXT);
    $PAGE->navbar->add($fullname, '');
}

$PAGE->set_url($CFG->wwwroot .'/blocks/samieattendance/showattendances.php?courseid='.$courseid);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'block_samieattendance'));
echo $OUTPUT->header();

$courseid = required_param('courseid', PARAM_INT);
$date = optional_param('date', null, PARAM_TEXT);
$userid = optional_param('userid', null, PARAM_INT);
if (isset($date)) {
    $out .= html_writer::start_tag('p');
    $out .= html_writer::tag('b', get_string('attendanceson', 'block_samieattendance')).
            block_samieattendance_format_human_timedate($date);
    $out .= html_writer::end_tag('p');
    $out .= block_samieattendance_print_attendance_table($courseid, $date);
} else if (isset($userid)) {
    $out .= html_writer::start_tag('p');
    $out .= html_writer::tag('b', get_string('attendanceson', 'block_samieattendance')).$fullname;
    $out .= html_writer::end_tag('p');
    $out .= block_samieattendance_print_attendance_table_by_user($courseid, $userid);
} else {
    $out .= html_writer::start_tag('p');
    $out .= html_writer::tag('b', get_string('attendanceson', 'block_samieattendance')).
            $course->fullname;
    $out .= html_writer::end_tag('p');
    $out .= block_samieattendance_print_dates_attendance($courseid);
}
echo $out;
echo $OUTPUT->footer();