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
 * Moodle standard lib.
 *
 * @package    local_quizattemptexport_kassel
 * @author     Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @copyright  Ralf Wiederhold 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_quizattemptexport_kassel_extend_settings_navigation(\settings_navigation $settingsnav, $context) {
    global $CFG, $PAGE;

    // We only want to work with module context.
    if (!($context instanceof \context_module)) {
        return;
    }

    // Check if it is a quiz module.
    $cm = get_coursemodule_from_id('quiz', $context->instanceid);
    if (empty($cm)) {
        return;
    }

    // Make sure the current user may see our settings node.
    if (!has_any_capability(array('mod/quiz:viewreports', 'mod/quiz:grade'), $context)) {
        return;
    }

    // Get the quiz settings node.
    $settingnode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);

    // Add our node.
    $text = get_string('nav_exportoverview', 'local_quizattemptexport_kassel');
    $url = new moodle_url('/local/quizattemptexport_kassel/overview.php', array('cmid' => $context->instanceid));
    $foonode = navigation_node::create(
        $text,
        $url,
        navigation_node::NODETYPE_LEAF,
        $text,
        'quizattemptexportexportoverview',
        new pix_icon('t/download', $text)
    );
    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $foonode->make_active();
    }
    $settingnode->add_node($foonode);
}