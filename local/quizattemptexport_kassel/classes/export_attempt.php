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
 * Interface that may be used to export a quiz attempt as
 * a PDF.
 *
 * @package    local_quizattemptexport_kassel
 * @author     Ralf Wiederhold <ralf.wiederhold@eledia.de>
 * @copyright  Ralf Wiederhold 2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizattemptexport_kassel;

defined('MOODLE_INTERNAL') || die();

class export_attempt {

    private $page;
    private $output;

    private $exportpath;

    /** @var \quiz_attempt $attempt_obj */
    private $attempt_obj;
    private $user_rec;
    private $attempt_rec;
    private $quiz_rec;
    private $accessmanager;

    public function __construct($attempt) {
        global $SITE, $CFG, $PAGE, $DB;

        static $pagecontext;
        static $pagecourse;

        // Some themes are not compatible so we can just switch to another.
        $CFG->theme = get_config('local_quizattemptexport_kassel', 'theme');

        // Some icons are generated as svg image. We have to deactivate this.
        $CFG->svgicons = false;

        $this->initialize_attempt($attempt);

        if (!$this->user_rec = $DB->get_record('user', array('id' => $this->attempt_obj->get_userid()))) {

            $exc = new \moodle_exception('except_usernotfound', 'local_quizattemptexport_kassel', '', $this->attempt_obj->get_userid());
            $this->logmessage($exc->getMessage());

            throw $exc;
        }
        /*
        // idnumber currently not used...
        if (empty($this->user_rec->idnumber)) {

            $exc = new \moodle_exception('except_usernoidnumber', 'local_quizattemptexport_kassel', '', $this->user_rec->id);
            $this->logmessage($exc->getMessage());

            throw $exc;
        }
        */


        try {
            $this->exportpath = $this->prepare_downloadarea();
        } catch (\moodle_exception $e) {
            $this->logmessage($e->getMessage());

            throw $e;
        }


        //create page object and set theme
        if (empty($PAGE)) {
            $this->page = new \moodle_page();
        } else {
            $this->page = $PAGE;
        }

        if (empty($pagecontext)) {
            $this->page->set_context(\context_system::instance());   // We also have to set the context.
            $pagecontext = $this->page->context;
        }

        if (empty($pagecourse)) {
            $this->page->set_course($SITE);
            $pagecourse = $this->page->course;
        }
        $this->page->set_url('/');
        $this->page->set_pagelayout('popup');
        $this->page->set_pagetype('site-index'); //necessary, or the current url will be used automatically
        //$this->page->theme->force_svg_use(false); //we need to use png-type icons

        //get the theme for the the default device type
        //we only need to set it if we want to explicitly use a specific theme
        //$themename = core_useragent::get_device_type_theme(core_useragent::DEVICETYPE_DEFAULT);
        //$this->page->force_theme($themename);

        //create the core-renderer
        $this->output = $this->page->get_renderer('core', null, RENDERER_TARGET_GENERAL);
    }

    /**
     * Retrieves the code used for enrolment into the assessment course
     * by the user the currently processed attempt belongs to.
     *
     * If we are not able to find a code this method returns
     * a placeholder string.
     *
     * @return string
     * @throws \dml_exception
     */
    private function get_coursecode() {
        global $DB;

        $enrolkey = 'n/a';
        $course = $this->attempt_obj->get_course();
        $userid = $this->attempt_obj->get_userid();

        $sql = 'SELECT ue.*
                FROM 
                    {user_enrolments} ue,
                    {enrol} e
                WHERE
                    e.enrol = :enrolname
                AND 
                    e.courseid = :courseid
                AND 
                    ue.enrolid = e.id
                AND 
                    ue.userid = :userid';
        $params = [
            'enrolname' => 'elediamultikeys',
            'courseid' => $course->id,
            'userid' => $userid
        ];

        if ($enrolments = $DB->get_records_sql($sql, $params)) {
            $enrolment = array_shift($enrolments);

            // Make sure required plugin table exists.
            if($tables = $DB->get_tables()) {

                if (in_array('block_eledia_multikeys', $tables)) {

                    if ($keyrec = $DB->get_record('block_eledia_multikeys', ['enrolid' => $enrolment->enrolid, 'user' => $userid])) {
                        $enrolkey = $keyrec->code;
                    }
                }
            }
        }

        return $enrolkey;
    }

    /**
     * Checks if there is a download area for exports of the quiz the currently
     * processed attempt belongs to within the configured directory. If there is
     * no such area the method tries to create one.
     *
     * Trows an exception if  either no base directory has been configured or the
     * base directory turns out to not be writable.
     *
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function prepare_downloadarea() {

        $course = $this->attempt_obj->get_course();
        $export_dir = get_config('local_quizattemptexport_kassel', 'pdfexportdir');

        if (!is_dir($export_dir)) {
            throw new \moodle_exception('except_dirmissing', 'local_quizattemptexport_kassel', '', $export_dir);
        }

        $coursename = clean_param($course->fullname, PARAM_SAFEDIR);

        $dirname = $course->id . '_' . $coursename;
        $exportpath = $export_dir . '/' . $dirname;

        if (!is_dir($exportpath)) {
            if (!mkdir($exportpath)) {
                throw new \moodle_exception('except_dirnotwritable', 'local_quizattemptexport_kassel', '', $export_dir);
            }
        }

        return $exportpath;
    }

    private function initialize_attempt($attempt) {

        $this->attempt_obj = $attempt;
        $this->attempt_rec = $this->attempt_obj->get_attempt();
        $this->quiz_rec = $this->attempt_obj->get_quiz();
        $this->accessmanager = $this->attempt_obj->get_access_manager(time());
    }

    public function export_pdf($filename = null) {
        global $CFG;

        ob_start();

        echo $this->page_header();
        echo $this->custom_css_simple();
        echo $this->report_header();
        echo $this->generate_attempt_output();
        echo $this->page_footer();

        $content = ob_get_contents();
        ob_end_clean();

        // Some replacements for better compatibility.
        $content = str_replace('<label', '<span class="quizanswer"', $content);
        $content = str_replace('</label>', '</span>', $content);
//file_put_contents($CFG->dataroot.'/test.html', $content);
//echo 'done';
//exit;
        $this->generate_pdf($content, $filename);

    }

    /**
     * Get a simplyfied header to reduce the errors while creating pdf.
     * @return string
     */
    protected function page_header() {

        return '<!DOCTYPE html>
        <html  dir="ltr" lang="de" xml:lang="de">
            <body  id="page-site-index">
                <div id="page" class="container-fluid">
                    <div id="page-content" class="row-fluid">
                        <section id="region-main" class="span12">
                            <span class="notifications" id="user-notifications"></span><div role="main"><span id="maincontent"></span>
        ';
    }

    /**
     * Get a simplyfied footer to reduce the errors while creating pdf.
     * @return string
     */
    protected function page_footer() {
        return '
                        </section>
                    </div>
                </div>
            </body>
        </html>
        ';
    }

    protected function report_header() {
        global $CFG, $DB;

        // Prepare data.
        $course = $this->attempt_obj->get_course();
        $quiz = $this->quiz_rec;
        $coursecode = $this->get_coursecode();
        $attemptsubmittedtime = date('d.m.Y - H:i:s', $this->attempt_rec->timefinish);
        $attemptstartedtime = date('d.m.Y - H:i:s', $this->attempt_rec->timestart);

        // Prepare result data.
        $marksachieved = $this->attempt_obj->get_sum_marks();
        $grademultiplier = $quiz->grade / $quiz->sumgrades;
        $grademax = round($quiz->grade, 2);
        $gradeachieved = round($marksachieved * $grademultiplier, 2);
        $gradepercent = round($gradeachieved / $grademax * 100, 0);

        // Prepare result string.
        $params = [
            'grademax' => $grademax,
            'gradeachieved' => $gradeachieved,
            'gradepercent' => $gradepercent
        ];
        $attemptresultstr = get_string('attemptresult', 'local_quizattemptexport_kassel', $params);

        // Prepare data for template.
        $templatedata = [
            'coursename' => $course->fullname,
            'quizname' => $quiz->name,
            'studentname' => fullname($this->user_rec),
            //'matriculationid' => $this->user_rec->idnumber, // idnumber currently not used...
            'matriculationid' => $this->user_rec->username,
            'coursecode' => $coursecode,
            'attemptstarted' => $attemptstartedtime,
            'attemptended' => $attemptsubmittedtime,
            'attemptresult' => $attemptresultstr
        ];

        // Render template and return html.
        $renderer = $this->page->get_renderer('core');
        return $renderer->render_from_template('local_quizattemptexport_kassel/pdf_header', $templatedata);
    }

    /**
     * Get a simple css definition.
     * @return string
     */
    protected function custom_css_simple() {
        global $CFG;

        return '<style type="text/css">
            @page {
                margin-top: 20px;
                margin-bottom: 20px;
                margin-left: 50px;
                margin-right: 50px;
            }

            body {
                font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
                font-size: 10pt;
            }

            img.questioncorrectnessicon,
            .informationitem {
                display: none;
            }

            .ablock .prompt {
                margin-top: 10px;
                font-weight: bold;
            }

            .outcome {
                margin-top: 10px;
            }

            .que.multichoice div.answer div.correct span.quizanswer {
                background-image: url('.$CFG->wwwroot.'/local/quizattemptexport_kassel/pix/correct.png);
                background-repeat: no-repeat;
                background-position: right top;
                background-color: #fff;
            }

            .que.multichoice div.answer div.incorrect span.quizanswer {
                background-image: url('.$CFG->wwwroot.'/local/quizattemptexport_kassel/pix/incorrect.png);
                background-repeat: no-repeat;
                background-position: right top;
                background-color: #fff;
            }

            /* remove default correctness icon, since its positioning in pdf is off */
            .que.multichoice div.answer div.correct > img,
            .que.multichoice div.answer div.incorrect > img {
                display: none;
            }

            div.answer {
                display: table;
                width: 90%
            }
            div.answer .r0, div.answer .r1 {
                display: table-row;
            }

            div.answer div input {
                display: table-cell;
                vertical-align: top;
                width: 30px;
                padding-top: 1px;
                margin-top: 1px;
            }

            span.quizanswer {
                display: table-cell;
                padding-right: 50px;
                padding-bottom: 5px;
                padding-top: 5px;
                margin-top: 5px;
            }

            div.que {
                page-break-inside: avoid;
                page-break-before: always;
                border-style: solid;
                border-width: 1px;
                border-color: #dddddd;
                padding-left: 15px;
                padding-right: 15px;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            div.nobreak {
                page-break-after: avoid;
                page-break-before: auto;
            }
            
            /**
                table styling
             */
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            table th,
            table td {
                padding: 5px 10px;
                border: 1px solid #000;
                text-align: left;
            }
            
            table.reportheader th {
                text-align: right;
                width: 30%;
            }
            
            /*
                Hide specific links that are displayed by moodle if the
                user context the export happens in has review rights for
                the given attempt.
             */
            div.que div.commentlink,
            div.que div.editquestion {
                display: none;
            }
            
            /**
                Question header styling
            */
            div.info {
                background-color: #dddddd;
                margin-top: 10px;
                padding: 10px;
            }
            
            div.info h3 {
                margin: 0 0 10px 0;
                padding: 0 0 5px 5px;
                border-bottom: 1px solid #000;
            }
            
            div.info .state,
            div.info .grade {
                font-weight: bold;
                margin: 10px 0 0 10px;
            }
            
            /**
                question sections styling
             */
             div.comment,
             div.outcome,
             div.formulation,
             div.history {
                border: 1px solid #000;
                margin: 10px 0;
                padding: 10px;
             }
             
             div.comment h4,
             div.outcome h4,
             div.formulation h4,
             div.history h4 {
                margin: 0 0 10px 0;
             }
        </style>';
    }

    protected function generate_pdf($html, $filename = null) {
        global $CFG;

        require_once($CFG->dirroot . '/local/quizattemptexport_kassel/vendor/dompdf/autoload.inc.php');

        set_time_limit(0);
        ob_start();// tcpdf doesnt like outputs here.

        $pdf = null;
        try {

            $options = array(
                'debug_layout' => false,
                'debug_css' => false,
                'enable_remote' => true,
                'enable_html5_parser' => true,
                'enable_css_float' => true,
                'log_output_file' => $CFG->dataroot . '/quizattemptexport_kassel_pdf_log.html',
                'temp_dir' => $CFG->tempdir
            );

            // instantiate and use the dompdf class
            $dompdf = new \Dompdf\Dompdf();
            $dompdfOptions = new \Dompdf\Options($options);
            $dompdf->setOptions($dompdfOptions);


            //manually define A4 paper size
            $dompdf->set_paper('a4');

            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdf = $dompdf->output();

        } catch (\Exception $exc) {
            ob_start();
            print_r($exc);
            $debug_out = ob_get_contents();
            ob_end_clean();
            $this->logmessage($debug_out);
        }

        ob_end_clean();

        if(!empty($pdf)) {

            // Generate filename if none has been provided.
            if (empty($filename)) {

                // Generate filename that contains sha256 hash of PDF content.
                $contenthash = hash('sha256', $pdf);
                //$filename = $this->user_rec->idnumber . '_' . $this->attempt_obj->get_courseid() . '_' . $contenthash . '.pdf'; //idnumber currenlty not used...
                $filename = $this->user_rec->username . '_' . $this->attempt_obj->get_courseid() . '_' . $contenthash . '.pdf';
            }

            // Write file.
            $res = fopen($this->exportpath . '/' . $filename, 'w');
            fwrite($res, $pdf);
            fclose($res);
        }

    }

    protected function generate_attempt_output() {
        global $USER, $DB;

        //set up vars required by copypasta
        $page = 0;
        $showall = true;


        $options = $this->attempt_obj->get_display_options(true);
        $options->flags = 0; // The flags attribute has to be "0".
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->correctness = \question_display_options::VISIBLE;

        $slots = $this->attempt_obj->get_slots();
        $headtags = $this->attempt_obj->get_html_head_contributions($page, $showall);
        $this->accessmanager->setup_attempt_page($this->page);

        $this->page->set_title(format_string($this->attempt_obj->get_quiz_name()));
        $this->page->set_heading($this->attempt_obj->get_course()->fullname);

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
        //COPYPASTA aus mod/quiz/review.php

        // Work out some time-related things.
        $overtime = 0;
        if ($this->attempt_rec->timefinish) {
            if ($timetaken = ($this->attempt_rec->timefinish - $this->attempt_rec->timestart)) {
                if ($this->quiz_rec->timelimit && $timetaken > ($this->quiz_rec->timelimit + 60)) {
                    $overtime = $timetaken - $this->quiz_rec->timelimit;
                    $overtime = format_time($overtime);
                }
                $timetaken = format_time($timetaken);
            } else {
                $timetaken = "-";
            }
        } else {
            $timetaken = get_string('unfinished', 'quiz');
        }

        // Prepare summary informat about the whole attempt.
        $summarydata = array();

        //always show student-info
        //if (!$attemptobj->get_quiz()->showuserpicture && $attemptobj->get_userid() != $USER->id) {

        $student = $DB->get_record('user', array('id' => $this->attempt_obj->get_userid()));
        $usrepicture = new \user_picture($student);
        $usrepicture->courseid = $this->attempt_obj->get_courseid();
        $summarydata['user'] = array(
            'title'   => $usrepicture,
            'content' => new \action_link(new \moodle_url('/user/view.php', array(
                'id' => $student->id, 'course' => $this->attempt_obj->get_courseid())),
                fullname($student, true)),
        );
        //}
        if ($this->attempt_obj->has_capability('mod/quiz:viewreports')) {
            $attemptlist = $this->attempt_obj->links_to_other_attempts($this->attempt_obj->review_url(null, $page,
                $showall));
            if ($attemptlist) {
                $summarydata['attemptlist'] = array(
                    'title'   => get_string('attempts', 'quiz'),
                    'content' => $attemptlist,
                );
            }
        }

        // Timing information.
        $summarydata['startedon'] = array(
            'title'   => get_string('startedon', 'quiz'),
            'content' => userdate($this->attempt_rec->timestart),
        );

        if ($this->attempt_rec->timefinish) {
            $summarydata['completedon'] = array(
                'title'   => get_string('completedon', 'quiz'),
                'content' => userdate($this->attempt_rec->timefinish),
            );
            $summarydata['timetaken'] = array(
                'title'   => get_string('timetaken', 'quiz'),
                'content' => $timetaken,
            );
        }

        if (!empty($overtime)) {
            $summarydata['overdue'] = array(
                'title'   => get_string('overdue', 'quiz'),
                'content' => $overtime,
            );
        }

        // Show marks (if the user is allowed to see marks at the moment).
        $grade = quiz_rescale_grade($this->attempt_rec->sumgrades, $this->quiz_rec, false);
        if ($options->marks >= \question_display_options::MARK_AND_MAX && quiz_has_grades($this->quiz_rec)) {

            if (!$this->attempt_rec->timefinish) {
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => get_string('attemptstillinprogress', 'quiz'),
                );

            } else if (is_null($grade)) {
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => quiz_format_grade($this->quiz_rec, $grade),
                );

            } else {
                // Show raw marks only if they are different from the grade (like on the view page).
                if ($this->quiz_rec->grade != $this->quiz_rec->sumgrades) {
                    $a = new \stdClass();
                    $a->grade = quiz_format_grade($this->quiz_rec, $this->attempt_rec->sumgrades);
                    $a->maxgrade = quiz_format_grade($this->quiz_rec, $this->quiz_rec->sumgrades);
                    $summarydata['marks'] = array(
                        'title'   => get_string('marks', 'quiz'),
                        'content' => get_string('outofshort', 'quiz', $a),
                    );
                }

                // Now the scaled grade.
                $a = new \stdClass();
                $a->grade = \html_writer::tag('b', quiz_format_grade($this->quiz_rec, $grade));
                $a->maxgrade = quiz_format_grade($this->quiz_rec, $this->quiz_rec->grade);
                if ($this->quiz_rec->grade != 100) {
                    $a->percent = \html_writer::tag('b', format_float(
                        $this->attempt_rec->sumgrades * 100 / $this->quiz_rec->sumgrades, 0));
                    $formattedgrade = get_string('outofpercent', 'quiz', $a);
                } else {
                    $formattedgrade = get_string('outof', 'quiz', $a);
                }
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => $formattedgrade,
                );
            }
        }

        // Feedback if there is any, and the user is allowed to see it now.
        $feedback = $this->attempt_obj->get_overall_feedback($grade);
        if ($options->overallfeedback && $feedback) {
            $summarydata['feedback'] = array(
                'title'   => get_string('feedback', 'quiz'),
                'content' => $feedback,
            );
        }


/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

        $quiz_renderer = $this->page->get_renderer('mod_quiz');
        //echo $quiz_renderer->review_summary_table($summarydata, $page);
        $output = $quiz_renderer->questions($this->attempt_obj, true, $slots, $page, $showall, $options);

        // Put an empty div inside the questions box to prevent a pagebreak on this place.
        $searchpattern = '#<div id=".*?" class="que.*?</div>#';
        if (preg_match_all($searchpattern, $output, $founds)) {
            if (!empty($founds[0])) {
                foreach ($founds[0] as $replsearch) {
                    $output = str_replace($replsearch, '</div>&nbsp;<div class="nobreak">'.$replsearch, $output);
                }
            }
        }

        // Make the title for the correct answer strong.
        $searchpattern = '#<div class="rightanswer">(.*?:).*?</div>#';
        if (preg_match_all($searchpattern, $output, $founds)) {
            if (!empty($founds[1])) {
                foreach ($founds[1] as $replsearch) {
                    $output = str_replace($replsearch, '<strong>'.$replsearch.'</strong>', $output);
                    break; // The result is alway the same. So we just need one replace.
                }
            }
        }

        return $output;
    }

    /**
     * Writes the given message into a logfile within
     * moodledata.
     *
     * @param string $msg
     */
    private function logmessage($msg) {
        global $CFG;

        $fh = fopen($CFG->dataroot . '/quizexport_kassel.log', 'w');
        $tprefix = date('d.m.Y - H:i:s');
        fwrite($fh, $tprefix . ' :' . $msg . "\n");
        fclose($fh);
    }
}
