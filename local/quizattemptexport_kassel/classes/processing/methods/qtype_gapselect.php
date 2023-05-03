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
 * Postprocessing implementation for qtype_gapselect
 *
 * @package		local_quizattemptexport
 * @copyright	2023 Ralf Wiederhold
 * @author		Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizattemptexport_kassel\processing\methods;

use local_quizattemptexport_kassel\processing\domdocument_util;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/quiz/attemptlib.php';
require_once $CFG->dirroot . '/mod/quiz/accessmanager.php';

class qtype_gapselect extends base {

    /**
     * Checks the questions response history (answer steps) for answers
     * the user has deleted. Deleted answers require some special handling in the
     * response history to be displayed a bit nicer...
     *
     * @param string $questionhtml
     * @param \quiz_attempt $attempt
     * @param int $slot
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function process(string $questionhtml, \quiz_attempt $attempt, int $slot): string {
        global $CFG, $DB;

        // Get DOM and XPath.
        $dom = domdocument_util::initialize_domdocument($questionhtml);
        $xpath = new \DOMXPath($dom);

        // Check for answers the user has deleted. Identified by a "todo" step
        // where each key within the steps data has an associated value of "0".
        $qattempt = $attempt->get_question_attempt($slot);
        $seqnoreqprocessing = [];
        foreach ($qattempt->get_step_iterator() as $seqno => $qastep) {
            if ($qastep->get_state() instanceof \question_state_todo) {
                $alldata = $qastep->get_all_data();
                $reducedvals = array_values(array_unique($alldata));
                if (count($reducedvals) == 1 && $reducedvals[0] == '0') {
                    $seqnoreqprocessing[] = $seqno;
                }
            }
        }

        if (!empty($seqnoreqprocessing)) {
            foreach($seqnoreqprocessing as $toprocess) {
                $cell2fix = $xpath->query('//div[@class="responsehistoryheader"]/table/tbody/tr[' . ($toprocess + 1) . ']/td[3]');
                if (!empty($cell2fix[0])) {

                    /** @var \DOMElement $cell2fix */
                    $cell2fix = $cell2fix[0];
                    $cell2fix->textContent = get_string('postprocessing_addedstr_answerdeleted', 'local_quizattemptexport_kassel');
                }
            }
        }

        // Save modified HTML and return.
        return domdocument_util::save_html($dom);
    }

}
