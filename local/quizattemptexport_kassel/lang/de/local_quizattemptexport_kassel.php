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
 * German language strings.
 *
 * @package    local_quizattemptexport_kassel
 * @author     Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @copyright  Ralf Wiederhold 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attemptresult'] = '{$a->gradeachieved} von {$a->grademax} Punkten ({$a->gradepercent}%)';
$string['except_dirmissing'] = 'Directory missing: {$a}';
$string['except_dirnotwritable'] = 'Directory is not writable: {$a}';
$string['except_usernoidnumber'] = 'User does not have an idnumber. User id: {$a}';
$string['except_usernotfound'] = 'User could not be found. User id: {$a}';
$string['label_coursename'] = 'Prüfung';
$string['label_quizname'] = 'Assessment';
$string['label_studentname'] = 'Student';
$string['label_matriculationid'] = 'Matrikelnummer';
$string['label_coursecode'] = 'Assessment Code';
$string['label_attemptstarted'] = 'Versuch gestartet';
$string['label_attemptended'] = 'Versuch beendet';
$string['label_attemptresult'] = 'Ergebnis';
$string['nav_exportoverview'] = 'Assessment Export Übersicht';
$string['page_overview_title'] = 'Exporte für "{$a}"';
$string['page_overview_attemptedreexport'] = 'Es wurde versucht den Versuch erneut zu exportieren.';
$string['page_overview_progressbar_step'] = 'Exportiere Versuch mit ID "{$a}".';
$string['page_overview_progressbar_finished'] = 'Exportieren aller Versuche abgeschlossen.';
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
$string['template_usersattemptslist_noattempts'] = 'Für dieses Quiz konnten keine Versuche gefunden werden.';
$string['template_usersattemptslist_nofiles'] = 'Für diesen Versuch konnten keine Dateien gefunden werden.';
$string['template_usersattemptslist_attemptfrom'] = 'Versuch vom';
$string['template_usersattemptslist_reexportattempttitle'] = 'Versuch erneut exportieren';
$string['template_usersattemptslist_exportall'] = 'Alle Versuche in dieser Quizinstanz erneut exportieren';
