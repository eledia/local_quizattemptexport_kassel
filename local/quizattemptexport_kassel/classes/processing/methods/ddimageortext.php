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
 * Postprocessing implementation for qtype_ddimageortext
 *
 * @package		local_quizattemptexport_kassel
 * @copyright	2020 Ralf Wiederhold
 * @author		Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizattemptexport_kassel\processing\methods;

use local_quizattemptexport_kassel\processing\domdocument_util;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/quiz/attemptlib.php';
require_once $CFG->dirroot . '/mod/quiz/accessmanager.php';

class ddimageortext extends base {

    public static function process(string $questionhtml, \quiz_attempt $attempt, int $slot) : string {
        global $CFG, $DB;

        // Get question attempt and question definition.
        $qa = $attempt->get_question_attempt($slot);
        $question = $qa->get_question();

        // Get the users drops from the question attempt data as well as the order of
        // the choices in the question instance.
        $userdrops = [];
        $instancechoicemapping = [];
        foreach ($qa->get_step_iterator() as $step) {

            // Build mapping of the choices defined in the question and the actual choice ordering
            // within the question instance.
            if ($step->get_state() instanceof \question_state_todo) {

                foreach ($step->get_all_data() as $key => $value) {

                    // Makes sure the key is a choice group.
                    if (0 === strpos($key, '_choiceorder')) {

                        // Get group identifier and ordered choices.
                        $group = (int) substr($key, -1); // Max of 8 within the plugin.
                        $values = explode(',', $value);

                        // Create mapping.
                        $instancechoicemapping[$group] = [];
                        foreach ($values as $instancechoicekey => $choicekey) {
                            $instancechoicemapping[$group][$instancechoicekey + 1] = $question->choices[$group][$choicekey];
                        }
                    }
                }
            }

            // Get the users drops from a complete answer.
            if ($step->get_state() instanceof \question_state_complete) {
                $userdrops = $step->get_all_data();
            }

            // Get the users drops from an incomplete answer.
            if ($step->get_state() instanceof \question_state_invalid) {
                $userdrops = $step->get_all_data();
            }
        }

        // Build map of drop zones and the actual drop the user placed
        // on the drop zone within the attempt.
        $dropzones = [];
        foreach ($question->places as $key => $dropzone) {
            $obj = new \stdClass;
            $obj->definition = $dropzone;
            $obj->drop = null;
            if (!empty($userdrops['p' . $key])) {

                $obj->drop = $instancechoicemapping[$dropzone->group][$userdrops['p' . $key]];
            }

            $dropzones[] = $obj;
        }


        // Start image generation.
        $fs = get_file_storage();

        // Get the background image from the specific question instance.
        $params = ['contextid' => $question->contextid, 'itemid' => $question->id, 'filearea' => 'bgimage', 'component' => 'qtype_ddimageortext'];
        $select = 'contextid = :contextid AND itemid = :itemid AND filearea = :filearea AND component = :component AND filesize <> 0';
        $dropbg = $DB->get_record_select('files', $select, $params);
        $bgfileinstance = $fs->get_file_instance($dropbg);
        $bgfilecontent = $bgfileinstance->get_content();
        $bgfileinfo = $bgfileinstance->get_imageinfo();

        // Calculate a somewhat fitting font size from the drop backgrounds height.
        $calculatedfontsize = (int) ($bgfileinfo['height'] / 15);

        // Load background into GD and render stuff onto it.
        $gdbgfile = imagecreatefromstring($bgfilecontent);
        foreach ($dropzones as $dropzone) {

            if (empty($dropzone->drop)) {
                continue;
            }

            $dropx = $dropzone->definition->xy[0];
            $dropy = $dropzone->definition->xy[1];

            // Render image or text?
            if (!empty($dropzone->drop->text)) {

                $textcolor = imagecolorallocate($gdbgfile, 0, 0, 0);
                $text = $dropzone->drop->text;
                $font = $CFG->dirroot . '/local/quizattemptexport_fom/font/Open_Sans/OpenSans-Regular.ttf';

                // Need to offset y-value as it starts top left for text, instead of bottom left as for other stuff.
                imagettftext($gdbgfile, $calculatedfontsize, 0, $dropx, $dropy + $calculatedfontsize, $textcolor, $font, $text);

            } else {

                // Get the drop file.
                $params = ['contextid' => $question->contextid, 'itemid' => $dropzone->drop->id, 'filearea' => 'dragimage', 'component' => 'qtype_ddimageortext'];
                $select = 'contextid = :contextid AND itemid = :itemid AND filearea = :filearea AND component = :component AND filesize <> 0';
                $dropfile = $DB->get_record_select('files', $select, $params);
                $dropfileinstance = $fs->get_file_instance($dropfile);
                $dropfilecontent = $dropfileinstance->get_content();
                $imageinfo = $dropfileinstance->get_imageinfo();

                // Load into GD.
                $gddropfile = imagecreatefromstring($dropfilecontent);

                // Render onto background and clean it up.
                imagecopymerge($gdbgfile, $gddropfile, $dropx, $dropy, 0, 0, $imageinfo['width'], $imageinfo['height'], 100);
                imagedestroy($gddropfile);
            }
        }

        // We only need the image content anyway, so just collect it from the output buffer
        // instead of writing to a temp file.
        ob_start();
        imagepng($gdbgfile);
        $imagecontent = ob_get_contents();
        ob_end_clean();

        // Clean up.
        imagedestroy($gdbgfile);

        // Get DOM and XPath.
        $dom = domdocument_util::initialize_domdocument($questionhtml);
        $xpath = new \DOMXPath($dom);

        // Rewrite SRC of background image with our generated image as a base64 encoded data url.
        $dataurl = 'data:image/png;base64,' . base64_encode($imagecontent);
        $backgrounds = $xpath->query('//img[starts-with(@class, "dropbackground")]');
        foreach ($backgrounds as $bg) {
            /** @var \DOMElement $bg */
            $bg->setAttribute('src', $dataurl);
        }

        return domdocument_util::save_html($dom);
    }
}
