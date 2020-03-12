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
 * English language strings.
 *
 * @package    local_quizattemptexport_kassel
 * @author     Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @copyright  Ralf Wiederhold 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attemptresult'] = '{$a->gradeachieved} of {$a->grademax} marks ({$a->gradepercent}%)';
$string['except_dirmissing'] = 'Directory missing: {$a}';
$string['except_dirnotwritable'] = 'Directory is not writable: {$a}';
$string['except_usernoidnumber'] = 'User does not have an idnumber. User id: {$a}';
$string['except_usernotfound'] = 'User could not be found. User id: {$a}';
$string['label_coursename'] = 'Course';
$string['label_quizname'] = 'Assessment';
$string['label_studentname'] = 'Student';
$string['label_matriculationid'] = 'Matriculation ID';
$string['label_coursecode'] = 'Assessment code';
$string['label_attemptstarted'] = 'Attempt started';
$string['label_attemptended'] = 'Attempt ended';
$string['label_attemptresult'] = 'Assessment result';
$string['nav_exportoverview'] = 'Attempt export overview';
$string['plugindesc'] = 'Automatic export of quiz attempts with additional features for Uni Kassel.';
$string['pluginname'] = 'Uni Kassel - Assessment export';
$string['setting_pdfexportdir'] = 'Export path on server';
$string['setting_pdfexportdir_desc'] = 'This is the path of a directory within the moodledata directory on the host-server, the pdf-files will be saved to.';
$string['setting_theme'] = 'Theme for export';
$string['setting_theme_desc'] = 'Choose the moodle theme that should be used as the base styling when exporting quiz attempts. It is recommended to use one of the base themes that come bundled with moodle as using custom themes may lead to undesired results.';
