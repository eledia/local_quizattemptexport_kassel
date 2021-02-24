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
$string['ddimageortext_correctanswer_title'] = 'Correct answers';
$string['ddmarker_correctanswer_title'] = 'Correct answers';
$string['except_dirmissing'] = 'Directory missing: {$a}';
$string['except_dirnotwritable'] = 'Directory is not writable: {$a}';
$string['except_usernoidnumber'] = 'User does not have an idnumber. User id: {$a}';
$string['except_usernotfound'] = 'User could not be found. User id: {$a}';
$string['label_coursename'] = 'Exam';
$string['label_quizname'] = 'Assessment';
$string['label_studentname'] = 'Student';
$string['label_matriculationid'] = 'Matriculation ID';
$string['label_coursecode'] = 'Assessment code';
$string['label_attemptstarted'] = 'Attempt started';
$string['label_attemptended'] = 'Attempt ended';
$string['label_attemptresult'] = 'Assessment result';
$string['nav_exportoverview'] = 'Attempt export overview';
$string['page_overview_title'] = 'Exports for "{$a}"';
$string['page_overview_attemptedreexport'] = 'Attempted to export the attempt again.';
$string['page_overview_progressbar_step'] = 'Exporting attempt with id "{$a}".';
$string['page_overview_progressbar_finished'] = 'Finished exporting all attempts.';
$string['plugindesc'] = 'Automatic export of quiz attempts with additional features for Uni Kassel.';
$string['pluginname'] = 'Uni Kassel - Assessment export';
$string['setting_autoexport'] = 'Enable automatic export';
$string['setting_autoexport_desc'] = 'Enable this setting to export each quiz attempt automatically when the user submits the attempt.';
$string['setting_pdfexportdir'] = 'Export path on server';
$string['setting_pdfexportdir_desc'] = 'This is the path of a directory within the moodledata directory on the host-server, the pdf-files will be saved to.';
$string['setting_pdfgenerationtimeout'] = 'Timeout for PDF generation (seconds)';
$string['setting_pdfgenerationtimeout_desc'] = 'Set the timeout in seconds that should apply for the generation of the PDF files. If the generation process has not finished after the given amount of time the process will be cancelled. Set a value of 0 to deactivate the timeout.';
$string['setting_theme'] = 'Theme for export';
$string['setting_theme_desc'] = 'Choose the moodle theme that should be used as the base styling when exporting quiz attempts. It is recommended to use one of the base themes that come bundled with moodle as using custom themes may lead to undesired results.';
$string['task_generate_pdf_name'] = 'Generate attempt PDFs';
$string['template_usersattemptslist_noattempts'] = 'Could not find any attempts for this quiz.';
$string['template_usersattemptslist_nofiles'] = 'Could not find any files for this attempt.';
$string['template_usersattemptslist_attemptfrom'] = 'Attempt from';
$string['template_usersattemptslist_reexportattempttitle'] = 'Export attempt again';
$string['template_usersattemptslist_exportall'] = 'Re-export all attempts within this quiz instance';

$string['envcheck_execfailed'] = 'Error when trying to execute CLI call.';
$string['envcheck_sharedlibsmissing'] = 'The binary is missing shared libraries: {$a}';
$string['envcheck_success'] = 'The environment check succeeded. All dependencies are met.';