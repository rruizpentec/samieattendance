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
 * Process ajax requests to enhance UX.
 *
 * @package    SAMIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ .'/../../config.php');

defined('MOODLE_INTERNAL') || die;

global $CFG, $DB, $USER;

require_once(__DIR__ .'/lib.php');

$aluid = required_param('alu_id', PARAM_INT);
$courseid = required_param('course_id', PARAM_INT);
$asifecha = required_param('asi_fecha', PARAM_TEXT);
$action = required_param('action', PARAM_TEXT);

try {
    if ($action == 'setAttendance' || $action == 'delAttendance') {
        $clause = ' timedate = :timedate AND courseid = :courseid AND userid = :userid';
        $transaction = $DB->start_delegated_transaction();
        $record = new stdClass();
        if ($action == 'setAttendance') {
            $record->courseid = $courseid;
            $record->userid = $aluid;
            $record->timedate = $asifecha;
            $record->teacherid = $USER->id;
            try {
                $DB->insert_record('block_samieattendance_att', $record);
            } catch (Exception $ex) {
                $attendanceexists = $DB->record_exists('block_samieattendance_att',
                        array('courseid' => $courseid, 'userid' => $aluid, 'timedate' => $asifecha));
                if (!$attendanceexists) {
                    $result = 'fail';
                    die();
                }
            }
        } else if ($action == 'delAttendance') {
            $DB->delete_records('block_samieattendance_att', array(
                'timedate' => $asifecha,
                'userid'   => $aluid,
                'courseid' => $courseid));
        }
        $samieconfig = get_config('package_samie');
        $baseurl = $samieconfig->baseurl;
        if (substr($baseurl, -1, 1) != '/') {
            $baseurl .= '/';
        }
        if ($aluid != $USER->id) {
            $result = file_get_contents($baseurl.'inc/attendancerequests.php?action='. $action. '&alu_id='.
                    $aluid .'&course_id=' . $courseid . '&asi_fecha=' . block_samieattendance_format_machine_timedate($asifecha));
            $result = 'OK';
        } else {
            $result = 'OK';
        }
        if (strcmp($result, 'OK') == 0) {
            $transaction->allow_commit();
            echo 'OK';
        } else {
            echo 'failed';
        }
    }
} catch (Exception $e) {
        $transaction->rollback($e);
        echo 'exception';
}