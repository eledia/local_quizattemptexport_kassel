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
 * Overview page for quiz attempt exports.
 *
 * @package    local_quizattemptexport_kassel
 * @author     Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @copyright  Ralf Wiederhold 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

// Get cmid of quiz instance.
$cmid = required_param('cmid', PARAM_INT);


// Get course module, quiz instance and context.
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$instance = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);


// Check access.
require_login($cm->course, false);
if (!has_any_capability(array('mod/quiz:viewreports', 'mod/quiz:grade'), $context)) {
    $capability = 'mod/quiz:viewreports';
    throw new required_capability_exception($context, $capability, 'nopermission', '');
}

echo 'Ich bin ein Platzhalter. Mehr gibt es hier noch nicht zu sehen.';
