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
 * Export files from the Brightcove activity.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_brightcove;

defined('MOODLE_INTERNAL') || die();

/**
 * Export files from the Brightcove activity.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_file {

    /**
     * Maps file mime types to extenstions.
     * @var array
     */
    protected $mimemap = array(
            'text/plain' => '.txt'
    );

    /**
     * Initialises the class.
     *
     * @return void
     */
    public function __construct($filename, $mimetype) {
        $this->mimetype = $mimetype;
        $this->filename = $this->format_filename($filename);
    }

    /**
     * Given a raw file name, returns cleaned filename
     * with extension.
     *
     * @param string $filename Input filename.
     * @return string $filename Cleaned filename with extension.
     */
    protected function format_filename($filename) {
        $extension = $this->mimemap[$this->mimetype];
        $filename = clean_filename($filename);
        $filename .= $extension;

        return $filename;
    }

    /**
     * Output file headers to initialise the download of the file.
     */
    public function send_header() {
        global $CFG;

        if (defined('BEHAT_SITE_RUNNING')) {
            // For text based formats - we cannot test the output with behat if we force a file download.
            return;
        }
        if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
            header('Cache-Control: max-age=10');
            header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
        }
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header("Content-Type: $this->mimetype\n");
        header("Content-Disposition: attachment; filename=\"$this->filename\"");
    }
}