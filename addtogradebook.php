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
 * This file will add / update the students attendance grades.
 *
 * @package    SAMIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ .'/lib.php');

defined('MOODLE_INTERNAL') || die;

global $CFG, $DB, $PAGE, $USER;

$context = context_system::instance();
require_login();

$PAGE->set_url($CFG->wwwroot .'/blocks/samieattendance/showattendances.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'block_samieattendance'));
$error = true;
$courseid = required_param('courseid', PARAM_INT);
echo $OUTPUT->header();
$gradeitem = block_samieattendance_get_attendance_grade_item($courseid);
if ($gradeitem) {
    $error = !block_samieattendance_update_attendance_users_grades($courseid, $gradeitem->id);
}

if (!$error) {
    $redirectto = $CFG->wwwroot .'/grade/report/grader/index.php?id='.$courseid;
    echo "<script>window.open('".$redirectto."','_self');</script>";
} else {
    echo get_string('errorupdatingscores', 'block_samieattendance');
}
echo $OUTPUT->footer();