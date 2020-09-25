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

use core\uuid;
use Knp\Snappy\Pdf;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/quiz/attemptlib.php';
require_once $CFG->dirroot . '/mod/quiz/accessmanager.php';

class export_attempt {

    private $page;
    private $output;

    private $exportpath;

    /** @var \quiz_attempt $attempt_obj */
    private $attempt_obj;
    private $user_rec;

    public function __construct(\quiz_attempt $attempt) {
        global $SITE, $CFG, $PAGE, $DB;

        static $pagecontext;
        static $pagecourse;

        // Some themes are not compatible so we can just switch to another.
        $CFG->theme = get_config('local_quizattemptexport_kassel', 'theme');

        // Some icons are generated as svg image. We have to deactivate this.
        $CFG->svgicons = false;

        $this->attempt_obj = $attempt;

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

        $exportdir = get_config('local_quizattemptexport_kassel', 'pdfexportdir');

        if (!is_dir($exportdir)) {
            throw new \moodle_exception('except_dirmissing', 'local_quizattemptexport_kassel', '', $exportdir);
        }

        /*
        // TODO Only one central download directory for now.
        $course = $this->attempt_obj->get_course();
        $coursename = clean_param($course->fullname, PARAM_SAFEDIR);

        $dirname = $course->id . '_' . $coursename;
        $exportpath = $exportdir . '/' . $dirname;

        if (!is_dir($exportpath)) {
            if (!mkdir($exportpath)) {
                throw new \moodle_exception('except_dirnotwritable', 'local_quizattemptexport_kassel', '', $exportdir);
            }
        }

        return $exportpath;
        */
        return $exportdir;
    }


    public function export_pdf() {
        global $CFG, $DB;

        // Generate the HTML content to be rendered into a PDF.
        $generator = new generate_attempt_html($this->page);
        $html = $generator->generate($this->attempt_obj);

        // Load vendor requirements.
        require_once $CFG->dirroot . '/local/quizattemptexport_kassel/vendor/autoload.php';

        // Set up some processing requirements.
        set_time_limit(0);
        ob_start();// tcpdf doesnt like outputs here.

        // Generate temp file name for pdf generation.
        $tempexportfile = $CFG->tempdir . '/' . uuid::generate() . '.pdf';

        // Decide which wkhtmltopdf binary to use.
        $osinfo = php_uname('s');
        $binarypath = $CFG->dirroot . '/local/quizattemptexport_kassel/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64';
        if (false !== strpos($osinfo, 'Windows')) {
            $binarypath = $CFG->dirroot . '/local/quizattemptexport_kassel/vendor/wemersonjanuario/wkhtmltopdf-windows/bin/64bit/wkhtmltopdf.exe';
        }

        // Create a log channel.
        $log = new \Monolog\Logger('snappy-wkhtmltopdf');
        $log->pushHandler(new \Monolog\Handler\StreamHandler($CFG->dataroot . '/quizattemptexport_snappy.log', \Monolog\Logger::ERROR));

        // Get the configured timeout for PDF generation. A settings value of 0 should deactivate the timeout, i.e. we use
        // NULL as the timeout value.
        $timeout = null;
        if ($settingstimeout = get_config('local_quizattemptexport_kassel', 'pdfgenerationtimeout')) {
            $settingstimeout = (int) $settingstimeout;
            if ($settingstimeout < 0) {
                $settingstimeout = null;
            }
            $timeout = $settingstimeout;
        }


        try {
            // Start pdf generation and write into a temp file.
            $snappy = new Pdf();
            $snappy->setLogger($log);
            $snappy->setTemporaryFolder($CFG->tempdir);
            $snappy->setTimeout($timeout);

            $snappy->setOption('toc', false);
            $snappy->setOption('no-outline', true);
            $snappy->setOption('images', true);
            $snappy->setOption('enable-local-file-access', true);
            $snappy->setOption('enable-external-links', true);
            $snappy->setBinary($binarypath);
            $snappy->generateFromHtml($html, $tempexportfile);

        } catch (\Exception $exc) {

            // Check if file really was not generated or if the error returned
            // by wkhtmltopdf may have been non-critical.

            if (!file_exists($tempexportfile) || !filesize($tempexportfile)) {
                ob_start();
                echo $exc->getMessage();
                echo "\n";
                echo $exc->getTraceAsString();
                $debug_out = ob_get_contents();
                ob_end_clean();
                $this->logmessage($debug_out);
                return;
            }
        }

        // Get content from temp file for further processing and clean up.
        $tempfilecontent = file_get_contents($tempexportfile);
        unlink($tempexportfile);

        // Generate the parts of the target file name.

        // Quiz instance name
        $cm = $this->attempt_obj->get_cm();
        $instance = $DB->get_record('quiz', ['id' => $cm->instance]);
        $quizname = $instance->name;

        // The users login name.
        $username = $this->user_rec->username;

        // The attempts id for uniqueness.
        $attemptid = $this->attempt_obj->get_attemptid();

        // The current time for more uniqueness.
        $time = date('YmdHis', time());

        // The sha256 hash of the file content for validation purposes.
        $contenthash = hash('sha256', $tempfilecontent);

        // Piece the file name parts together.
        $filename = $quizname . '_' . $username . '_' . $attemptid . '_' . $time . '_' . $contenthash . '.pdf';


        // TODO local filname might require milliseconds instead of seconds.
        // Write file into the defined export dir, so it may be archived using sftp.
        $localfilepath = $this->exportpath . '/' . $filename;
        file_put_contents($localfilepath, $tempfilecontent);

        // Debug output...
        //file_put_contents($localfilepath . '.html', $html);


        // Write file into moodle file system for web access to the files.
        $cm = $this->attempt_obj->get_cm();
        $context = \context_module::instance($cm->id);

        $filedata = new \stdClass;
        $filedata->contextid = $context->id;
        $filedata->component = 'local_quizattemptexport_kassel';
        $filedata->filearea = 'export';
        $filedata->itemid = $this->attempt_obj->get_attemptid();
        $filedata->userid = $this->attempt_obj->get_userid();
        $filedata->filepath = '/';
        $filedata->filename = $filename;

        $fs = get_file_storage();
        $file = $fs->create_file_from_string($filedata, $tempfilecontent);


        // Clean up any unexpected output.
        ob_end_clean();
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
