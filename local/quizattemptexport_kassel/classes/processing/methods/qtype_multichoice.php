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
 * Postprocessing implementation for qtype_multichoice
 *
 * @package		local_quizattemptexport
 * @copyright	2021 Ralf Wiederhold
 * @author		Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizattemptexport_kassel\processing\methods;

use local_quizattemptexport_kassel\processing\domdocument_util;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/quiz/attemptlib.php';
require_once $CFG->dirroot . '/mod/quiz/accessmanager.php';

class qtype_multichoice extends base {

    /**
     * Replace font awesome correctness icons with locally embedded icons.
     *
     * Also checks for the questions response history (answer steps) for answers
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

        // Get correctness icons if any.
        $icons = $xpath->query('//div[@class="answer"]//i');
        foreach ($icons as $icon) {

            /** @var \DOMElement $icon */

            // Check attributes of element containing the icon for indication of correctness. Decide
            // which image to replace the icon with depending on type of correctness.
            $parentclassvals = $icon->parentNode->getAttribute('class');
            $replacementimgname = '';
            foreach (explode(' ', $parentclassvals) as $val) {
                if ($val == 'correct') {
                    $replacementimgname = 'grade_correct.png';
                } else if ($val == 'incorrect') {
                    $replacementimgname = 'grade_incorrect.png';
                } else if ($val == 'partiallycorrect') {
                    $replacementimgname = 'grade_partiallycorrect.png';
                }
            }

            // Replace icon node with image node containing a data url if a correctness type could be established.
            if (!empty($replacementimgname)) {

                $imgcontent = file_get_contents($CFG->dirroot . '/local/quizattemptexport_kassel/pix/' . $replacementimgname);
                $dataurl = 'data:image/png;base64,' . base64_encode($imgcontent);
                $replacementnode = $dom->createElement('img');
                $replacementnode->setAttribute('src', $dataurl);
                $replacementnode->setAttribute('class', 'correctnessicon');
                $replacementnode->setAttribute('width', '13');
                $replacementnode->setAttribute('height', '13');
                $icon->parentNode->replaceChild($replacementnode, $icon);
            }
        }

        // Check for answers the user has deleted. Identified by a "todo" step
        // with the key "answer" and a value of "-1" in its step data.
        $qattempt = $attempt->get_question_attempt($slot);
        $seqnoreqprocessing = [];
        foreach ($qattempt->get_step_iterator() as $seqno => $qastep) {
            if ($qastep->get_state() instanceof \question_state_todo) {
                $alldata = $qastep->get_all_data();
                if (!empty($alldata['answer']) && $alldata['answer'] === '-1') {
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

    public static function get_css(): string {
        return "
            .multichoice .ablock .prompt {
                margin-top: 10px;
                font-weight: bold;
            }
            
            .multichoice div.answer {
                display: table;
                width: 90%
            }
            .multichoice div.answer .r0, 
            .multichoice div.answer .r1 {
                display: table-row;
            }

            .multichoice div.answer div input {
                display: table-cell;
                vertical-align: top;
                width: 30px;
                padding-top: 1px;
                margin-top: 1px;
            }

            .multichoice div.answer div > * {
                display: table-cell;
            }

            .multichoice div.answer div > [data-region='answer-label'] {
                width: 100%;
            }

            .multichoice div.answer div > [data-region='answer-label'] .answernumber {
                min-width: 1.5em;
            }

            .multichoice div.answer div > .correctnessicon {
                min-width: 13px;
                margin-left: 5px;
            }
            
            .multichoice div.answer div > .specificfeedback {
                padding-left: 15px;
                min-width: 200px;
            }
        ";
    }

}
