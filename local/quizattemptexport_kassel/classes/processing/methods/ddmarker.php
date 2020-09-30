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
 * Postprocessing implementation for qtype_ddmarker
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

class ddmarker extends base {

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
        }

        // Build array of markers we need to render onto the background.
        $rendermarkers = [];
        foreach ($instancechoicemapping as $group => $groupchoices) {
            foreach ($groupchoices as $key => $option) {

                if (!empty($userdrops['c' . $key])) {
                    $obj = new \stdClass;
                    $obj->text = $option->text;
                    $obj->coords = explode(',', $userdrops['c' . $key]);
                    $rendermarkers[] = $obj;
                }
            }
        }

        // Start image generation.
        $fs = get_file_storage();

        // Get the background image from the specific question instance.
        $params = ['contextid' => $question->contextid, 'itemid' => $question->id, 'filearea' => 'bgimage', 'component' => 'qtype_ddmarker'];
        $select = 'contextid = :contextid AND itemid = :itemid AND filearea = :filearea AND component = :component AND filesize <> 0';
        $dropbg = $DB->get_record_select('files', $select, $params);
        $bgfileinstance = $fs->get_file_instance($dropbg);
        $bgfilecontent = $bgfileinstance->get_content();
        $bgfileinfo = $bgfileinstance->get_imageinfo();

        // Calculate a somewhat fitting font size from the drop backgrounds height.
        $calculatedfontsize = (int) ($bgfileinfo['height'] / 18);

        // Load background image and marker icon into GD.
        $markerimg = imagecreatefrompng($CFG->dirroot . '/local/quizattemptexport_kassel/pix/crosshairs.png');
        $gdbgfile = imagecreatefromstring($bgfilecontent);

        // Iterate the markers positioned by the user and render them onto the background.
        foreach ($rendermarkers as $marker) {

            $dropx = $marker->coords[0];
            $dropy = $marker->coords[1];

            $textcolor = imagecolorallocate($gdbgfile, 0, 0, 0);
            $text = $marker->text;
            $font = $CFG->dirroot . '/local/quizattemptexport_kassel/font/Open_Sans/OpenSans-Regular.ttf';

            $margin = 1;
            $border = 1;

            // Translate pt into px.
            $fontpix = $calculatedfontsize * 1.10; // Slightly off, but better result than 1.33

            // Get dimensions for text box.
            $textboxheight = $fontpix + 2 * $margin;
            $textlength = strlen($text);
            $textboxwidth = $fontpix * 0.7 * $textlength;
            $textboxwidth += $margin * 2;

            // Get starting positions for border rect and fill rect.
            $backgroundrect_x_from = $dropx;
            $backgroundrect_y_from = $dropy;
            $fillrect_x_from = $backgroundrect_x_from + $border;
            $fillrect_y_from = $backgroundrect_y_from + $border;

            // Get end positions for rectangles.
            $backgroundrect_x_to = $backgroundrect_x_from + ($textboxwidth + (2 * $border));
            $backgroundrect_y_to = $backgroundrect_y_from + ($textboxheight + (2 * $border));
            $fillrect_x_to = $fillrect_x_from + $textboxwidth;
            $fillrect_y_to = $fillrect_y_from + $textboxheight;

            // Colors...
            $bgcolor = imagecolorallocate($gdbgfile, 0, 0, 0);
            $fgcolor = imagecolorallocate($gdbgfile, 255, 255, 255);

            // Draw text background.
            imagefilledrectangle($gdbgfile, $backgroundrect_x_from, $backgroundrect_y_from, $backgroundrect_x_to, $backgroundrect_y_to, $bgcolor);
            imagefilledrectangle($gdbgfile, $fillrect_x_from, $fillrect_y_from, $fillrect_x_to, $fillrect_y_to, $fgcolor);

            // Draw marker icon over text backgrounds
            imagecopymerge($gdbgfile, $markerimg, $dropx - 7.5, $dropy - 7.5, 0, 0, 15, 15, 100);

            // Draw text onto its background.
            $text_x = $fillrect_x_from + $margin;
            $text_y = $fillrect_y_from + $margin + $calculatedfontsize;
            imagettftext($gdbgfile, $calculatedfontsize, 0, $text_x, $text_y, $textcolor, $font, $text);
        }

        // We only need the image content anyway, so just collect it from the output buffer
        // instead of writing to a temp file.
        ob_start();
        imagepng($gdbgfile);
        $imagecontent = ob_get_contents();
        ob_end_clean();

        // Clean up.
        imagedestroy($gdbgfile);
        imagedestroy($markerimg);

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
