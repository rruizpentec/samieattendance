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
 * @package    SAMIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ .'/../../config.php');

defined('MOODLE_INTERNAL') || die;

/**
 * This function will print the main table that have no filters
 * @param int The course id
 * @param string The date that will be filtered (date will be today)
 * @return string output
 */
function block_samieattendance_print_attendance_table($courseid, $date) {
    global $CFG, $DB;
    $table = '';
    $context = context_course::instance($courseid);
    $query = "SELECT u.id AS id, u.lastname, u.firstname, att.id AS attid,
                     (SELECT COUNT(DISTINCT(timedate)) as count
                        FROM {block_samieattendance_att} useratt
                       WHERE useratt.courseid = att.courseid AND u.id = useratt.userid) as studentattcount
                FROM {role_assignments} a, {user} u
           LEFT JOIN {block_samieattendance_att} att ON att.userid = u.id AND att.courseid = :courseid
                     AND att.timedate = :timedate
               WHERE contextid = :contextid
                     AND roleid = 5
                     AND a.userid = u.id
               ORDER BY lastname, firstname";
    $table .= html_writer::start_tag('table', array('class' => 'table table-condensed'));
    $table .= html_writer::start_tag('tr');
    $table .= html_writer::start_tag('th').get_string('student', 'block_samieattendance').html_writer::end_tag('th');
    $table .= html_writer::start_tag('th').get_string('attended', 'block_samieattendance').html_writer::end_tag('th');
    $table .= html_writer::start_tag('th').get_string('attendancepercentage', 'block_samieattendance').html_writer::end_tag('th');
    $table .= html_writer::end_tag('tr');
    $params = array('timedate' => $date, 'contextid' => $context->id, 'courseid' => $courseid);
    $rows = $DB->get_recordset_sql($query, $params);
    $totalattendances = $DB->count_records_select('block_samieattendance_att', 'courseid = :courseid',
            array('courseid' => $courseid), 'COUNT(DISTINCT(timedate)) as count');
    foreach ($rows as $row) {
        if ($row->attid == '') {
            $statusimgurl = $CFG->wwwroot.'/blocks/samieattendance/pix/fail.png';
            $value = 'fail';
        } else {
            $statusimgurl = $CFG->wwwroot.'/blocks/samieattendance/pix/ok.png';
            $value = 'ok';
        }
        $studentattcount = $row->studentattcount;
        $percentage = 0.0 + (($studentattcount * 100) / $totalattendances);
        $table .= html_writer::start_tag('tr');
        $fullname = $row->lastname.', '.$row->firstname;
        $table .= html_writer::start_tag('td');
        $table .= html_writer::tag('a', $fullname,
                array('href' => $CFG->wwwroot.'/blocks/samieattendance/showattendances.php?courseid='.
                        $courseid.'&userid='.$row->id.'&fullname='.$fullname)
        );
        $table .= html_writer::end_tag('td');
        $table .= html_writer::start_tag('td');
        $table .= html_writer::empty_tag('img',
                array('id'    => 'block_samieattendance_toggleUserAttendance'.$row->id,
                    'class'   => 'block_samieattendance_toggleUserAttendance',
                    'onclick' => "(function() {
                        require('block_samieattendance/samieattendance').toggle_user_attendance(".$courseid.",".$row->id.",".
                                $date.")
                    })();",
                    'value'   => $value,
                    'src'     => $statusimgurl)
        );
        $table .= html_writer::end_tag('td');
        $table .= html_writer::start_tag('td', array('id' => 'userAttendancePercentage'.$row->id));
        $table .= html_writer::empty_tag('input',
                array('type' => 'hidden', 'id' => 'studentAttendance'.$row->id, 'value' => $studentattcount)
        );
        $table .= html_writer::tag('span', number_format($percentage, 2).' %',
                array('id' => 'percentage'.$row->id)
        );
        $table .= html_writer::end_tag('td');
        $table .= html_writer::end_tag('tr');
    }
    $table .= html_writer::end_tag('table');
    $table .= html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => 'courseCountAttendance', 'value' => $totalattendances)
    );
    return $table;
}

/** This function will print the table that will display dates which students attended.
 * @param int The course id
 * @param int User id
 * @return string output
 */
function block_samieattendance_print_attendance_table_by_user($courseid, $userid) {
    global $DB, $CFG;
    $table = '';
    $results = $DB->get_recordset_select('block_samieattendance_att', 'userid = :userid', array('userid' => $userid),
            'timedate ASC', 'DISTINCT(timedate)');
    if (count($results) == 0) {
        $table .= html_writer::tag('span', get_string('noresults', 'block_samieattendance'));
    } else {
        $table .= html_writer::start_tag('table', array('class' => 'table table-condensed'));
        $table .= html_writer::start_tag('tr');
        $table .= html_writer::tag('th', get_string('dates', 'block_samieattendance'), array('colspan', '2'));
        $table .= html_writer::end_tag('tr');
        foreach ($results as $date) {
            $table .= html_writer::start_tag('tr');
            $linktodateatt = $CFG->wwwroot.'/blocks/samieattendance/showattendances.php?courseid='.
                    $courseid.'&date='.$date->timedate;
            $table .= html_writer::start_tag('td');
            $table .= html_writer::tag('a', block_samieattendance_format_human_timedate($date->timedate),
                    array('href' => $linktodateatt)
            );
            $table .= html_writer::end_tag('td');
            $table .= html_writer::end_tag('tr');
        }
        $table .= html_writer::end_tag('table');
    }
    return $table;
}

/** This function will print the table with number of attendances by date
 * @param int The course id
 * @return string output
 */
function block_samieattendance_print_dates_attendance($courseid) {
    global $DB;
    $table = '';
    $query = "SELECT att.timedate AS date, SUM(CASE WHEN userid = teacherid THEN 0 ELSE 1 END) AS count,
                     CONCAT(U.lastname, ' ', U.firstname) AS fullname
                FROM {block_samieattendance_att} att
          INNER JOIN {user} U ON att.teacherid = U.id
               WHERE att.courseid = :courseid
            GROUP BY att.timedate, U.id ";
    $params = array('courseid' => $courseid);
    $results = $DB->get_recordset_sql($query, $params);
    if (count($results) == 0) {
        $table .= html_writer::tag('span', get_string('noresults', 'block_samieattendance'));
    } else {
        $table .= html_writer::start_tag('table', array('class' => 'table table-condensed'));
        $table .= html_writer::start_tag('tr');
        $table .= html_writer::tag('th', get_string('dates', 'block_samieattendance'));
        $table .= html_writer::tag('th',
                block_samieattendance_get_course_rolename(5, $courseid, 'countusers')
        );
        $table .= html_writer::tag('th',
                block_samieattendance_get_course_rolename(3, $courseid, 'teacher')
        );
        $table .= html_writer::end_tag('tr');
        foreach ($results as $result) {
            $table .= html_writer::start_tag('tr');
            $table .= html_writer::start_tag('td');
            $table .= html_writer::tag('a', block_samieattendance_format_human_timedate($result->date),
                    array('href' => '?date='.$result->date.'&courseid='.$courseid)
            );
            $table .= html_writer::end_tag('td');
            $table .= html_writer::tag('td', $result->count);
            $table .= html_writer::tag('td', $result->fullname);
            $table .= html_writer::end_tag('tr');
        }
        $table .= html_writer::end_tag('table');
    }
    return $table;
}

/** This function get rolename if a course has custom role name, or put the rolename of language file
 * @param int The role id
 * @param int Course id
 * @param string String of language file
 * @return string role name
 */
function block_samieattendance_get_course_rolename($roleid, $courseid, $defaulttransname) {
    global $DB;
    $context = context_course::instance($courseid);
    $query = "SELECT RN.roleid AS roleid, RN.name AS rolename
                FROM {role_assignments} RA
          INNER JOIN {role_names} RN ON RA.roleid = RN.roleid
               WHERE RN.roleid = :roleid
                     AND RA.contextid = :contextid
            GROUP BY RN.roleid";
    $params = array('roleid' => $roleid, 'contextid' => $context->id);
    $roles = $DB->get_record_sql($query, $params);
    if ($roles) {
        return $roles->rolename;
    } else {
        return get_string($defaulttransname, 'block_samieattendance');
    }
}

/**
 * Format a timedate integer to a machine readable format
 *
 * @param int  $timedate Value to format
 */
function block_samieattendance_format_machine_timedate($timedate) {
    $year = floor($timedate / 10000);
    $month = floor(($timedate % 10000) / 100);
    if ($month < 10) {
        $month = '0'.$month;
    }
    $day = $timedate % 100;
    if ($day < 10) {
        $day = '0'.$day;
    }
    return $year.'-'.$month.'-'.$day;
}

/**
 * Format a timedate integer to a human readable format
 *
 * @param int  $timedate Value to format
 */
function block_samieattendance_format_human_timedate($timedate) {
    $year = floor($timedate / 10000);
    $month = floor(($timedate % 10000) / 100);
    if ($month < 10) {
        $month = '0'.$month;
    }
    $day = $timedate % 100;
    if ($day < 10) {
        $day = '0'.$day;
    }
    return $day.'-'.$month.'-'.$year;
}

/**
 * Adds a grade category related to a course
 *
 * @param int $courseid Course ID
 * return object Returns a grade_category object
 */
function block_samieattendance_add_course_grade_category($courseid) {
    global $DB;
    $gradecategory = new stdClass();
    $gradecategory->courseid = $courseid;
    $gradecategory->parent = null;
    $gradecategory->depth = 1;
    $gradecategory->fullname = '?';
    $gradecategory->aggregation = 13;
    $gradecategory->keephigh = 0;
    $gradecategory->droplow = 0;
    $gradecategory->aggregateonlygraded = 1;
    $gradecategory->aggregateoutcomes = 0;
    $gradecategory->timecreated = time();
    $gradecategory->timemodified = time();
    $gradecategory->hidden = 0;
    $gradecategory->id = $DB->insert_record('grade_categories', $gradecategory);
    if ($gradecategory->id) {
        $gradecategory->path = '/'.$gradecategory->id.'/';
        $DB->update_record('grade_categories', $gradecategory);
        return $gradecategory;
    } else {
        return null;
    }
}

/**
 * Gets the grade category linked to a course
 *
 * @param int $courseid Course ID
 * return object Returns a grade_category object
 */
function block_samieattendance_get_course_grade_category ($courseid) {
    global $DB;
    $firstgradecategory = $DB->get_record_sql("SELECT MIN(id) AS id FROM {grade_categories} WHERE courseid = :courseid",
            array('courseid' => $courseid)
    );
    if (!$firstgradecategory->id) {
        $firstgradecategory = block_samieattendance_add_course_grade_category($courseid);
        if ($firstgradecategory) {
            $maingrade = block_samieattendance_add_main_grade_item($courseid, $firstgradecategory->id);
            $gradeitem = block_samieattendance_add_attendance_grade_item($courseid);
            if ($maingrade && $gradeitem) {
                return $firstgradecategory;
            } else {
                $firstgradecategory = null;
            }
        } else {
            $firstgradecategory = null;
        }
    }
    return $firstgradecategory;
}

/**
 * Creates the first grade item for a course
 * @param int $courseid Course ID
 * @param int $gradecategoryid Grade category ID
 * return bool Operation result
 */
function block_samieattendance_add_main_grade_item($courseid, $gradecategoryid) {
    global $DB;
    try {
        // Grade item object.
        $gradebookitem = new stdClass();
        $gradebookitem->courseid = $courseid;
        $gradebookitem->itemtype = "course";
        $gradebookitem->iteminstance = $gradecategoryid;
        $gradebookitem->gradetype = 1;
        $gradebookitem->grademax = 10.0;
        $gradebookitem->grademin = 0.0;
        $gradebookitem->gradepass = 0.0;
        $gradebookitem->multfactor = 1.0;
        $gradebookitem->plusfactor = 0.0;
        $gradebookitem->aggregationcoef = 0.0;
        $gradebookitem->aggregationcoef2 = 0.0;
        $gradebookitem->sortorder = 1;
        $gradebookitem->display = 0;
        $gradebookitem->hidden = 0;
        $gradebookitem->locked = 0;
        $gradebookitem->locktime = 0;
        $gradebookitem->needsupdate = 0;
        $gradebookitem->weightoverride = 0;
        $gradebookitem->timecreated = time();
        $gradebookitem->timemodified = time();
        $DB->insert_record('grade_items', $gradebookitem);
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Adds Attendance as a course grade item
 * @param int $courseid Course ID
 */
function block_samieattendance_add_attendance_grade_item($courseid) {
    global $DB;
    $gradecategory = block_samieattendance_get_course_grade_category($courseid);
    $sortorder = $DB->get_field_sql('SELECT MAX(sortorder) AS sortorder FROM {grade_items}',
            array(
                'categoryid' => $gradecategory->id,
                'courseid'   => $courseid)
    );
    $gradebookitem = new stdClass();
    try {
        // Initialization of a grade item object.
        $gradebookitem->courseid = $courseid;
        $gradebookitem->categoryid = $gradecategory->id;
        $gradebookitem->itemname = BLOCK_SAMIEATTENDANCE_GRADEBOOK_ITEM_NAME;
        $gradebookitem->itemtype = "manual";
        $gradebookitem->itemmodule = BLOCK_SAMIEATTENDANCE_GRADEBOOK_ITEM_NAME;
        $gradebookitem->iteminstance = 1;
        $gradebookitem->itemnumber = 0;
        $gradebookitem->gradetype = 1;
        $gradebookitem->grademax = 10.0;
        $gradebookitem->grademin = 0.0;
        $gradebookitem->gradepass = 0.0;
        $gradebookitem->multfactor = 1.0;
        $gradebookitem->plusfactor = 0.0;
        $gradebookitem->aggregationcoef = 0.0;
        $gradebookitem->aggregationcoef2 = 0.0;
        $gradebookitem->sortorder = ($sortorder == null ? 1 : $sortorder + 1);
        $gradebookitem->display = 0;
        $gradebookitem->hidden = 0;
        $gradebookitem->locked = 0;
        $gradebookitem->locktime = 0;
        $gradebookitem->needsupdate = 0;
        $gradebookitem->weightoverride = 0;
        $gradebookitem->timecreated = time();
        $gradebookitem->timemodified = time();
        $gradebookitem->id = $DB->insert_record('grade_items', $gradebookitem);
    } catch (Exception $e) {
        return null;
    }
    return $gradebookitem;
}

/**
 * Gets the attendance grade item.
 *
 * @param int $courseid Course ID.
 * return object Grade category object
 */
function block_samieattendance_get_attendance_grade_item($courseid) {
    global $DB;
    // TEST $gradecategory = block_samieattendance_get_course_grade_category($courseid);.
    $gradebookitem = $DB->get_record('grade_items', array(
        'courseid' => $courseid,
        'itemname' => BLOCK_SAMIEATTENDANCE_GRADEBOOK_ITEM_NAME)
    );
    if (!$gradebookitem) {
        $gradebookitem = block_samieattendance_add_attendance_grade_item($courseid);
    }
    return $gradebookitem;
}

/**
 * Updates user attendance grade item
 *
 * @param object $user User attendance info (userid + user attendance in course)
 * @param int $itemid Grade item ID
 * @param double $percentage Percentage
 * return bool Operation result
 */
function block_samieattendance_update_user_grade($user, $gradeitemid, $percentage) {
    global $DB;
    try {
        $grade = new stdClass();
        $gradeid = $DB->get_field('grade_grades', 'id', array('userid' => $user->id, 'itemid' => $gradeitemid));
        if ($gradeid) {
             // Update existing data.
            $grade->id = $gradeid;
            $grade->rawgrade = $percentage;
            $grade->usermodified = $user->id;
            $grade->finalgrade   = $percentage;
            $grade->timecreated  = time();
            $grade->timemodified = time();
            $DB->update_record('grade_grades', $grade);
        } else {
            // Create row if data doesn't exist.
            $grade->itemid = intval($gradeitemid);
            $grade->userid = intval($user->id);
            $grade->rawgrade = $percentage;
            $grade->rawgrademax = 10.0;
            $grade->rawgrademin = 0.0;
            $grade->rawscaleid = null;
            $grade->usermodified = intval($user->id);
            $grade->finalgrade = floatval($percentage);
            $grade->hidden = 0;
            $grade->locked = 0;
            $grade->locktime = 0;
            $grade->exported = 0;
            $grade->overridden = 0;
            $grade->excluded = 0;
            $grade->feedback = null;
            $grade->feedbackformat = 0;
            $grade->information = null;
            $grade->informationformat = 0;
            $grade->timecreated = time();
            $grade->timemodified = time();
            $grade->aggregationstatus = 'used';
            $grade->aggregationweight = 1.0;
            $DB->insert_record('grade_grades', $grade);
        }
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Updates all course users attendance grade items
 *
 * @param object $courseid Course ID
 * @param int $gradeitemid Grade item ID
 * return bool Operation result
 */
function block_samieattendance_update_attendance_users_grades($courseid, $gradeitemid) {
    global $DB;
    $result = true;
    try {
        $subquery = "SELECT COUNT(DISTINCT(timedate))
                       FROM {block_samieattendance_att} att
                      WHERE c.id = att.courseid
                            AND u.id = att.userid ";
        $query = "SELECT u.id AS id, (".$subquery.") AS studentattendance
                    FROM {user} u, {role_assignments} r, {context} cx, {course} c
                   WHERE u.id = r.userid
                         AND r.contextid = cx.id
                         AND cx.instanceid = c.id
                         AND r.roleid = 5
                         AND cx.contextlevel = 50
                         AND c.id = :courseid
                   ORDER by lastname, firstname";
        $params = array('courseid' => $courseid);
        $users = $DB->get_recordset_sql($query, $params);
        $totalattendance = $DB->count_records_select('block_samieattendance_att', 'courseid = :courseid', $params,
                'COUNT(DISTINCT(timedate))');
        foreach ($users as $user) {
            $studentattendance = $user->studentattendance;
            $percentage = 0;
            if ($totalattendance != 0) {
                $percentage = (($studentattendance * 100) / $totalattendance) / 10;
            }
            if (!block_samieattendance_update_user_grade($user, $gradeitemid, $percentage)) {
                $result = false;
            }
        }
    } catch (Exception $e) {
        return false;
    }
    return $result;
}