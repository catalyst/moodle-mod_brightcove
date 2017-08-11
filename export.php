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
 * File download processing for Brightcove activity.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_brightcove\brightcove_api;
use mod_brightcove\export_file;

require(__DIR__.'/../../config.php');

$id             = required_param('id', PARAM_INT); // Course id.
$type           = required_param('type', PARAM_INT); // Download type.

$cm             = get_coursemodule_from_id('brightcove', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('brightcove', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
$moduleconfig = get_config('brightcove');

$brightcove = new brightcove_api($modulecontext);
$transcript = $brightcove->get_transcript_content($moduleinstance->videoid, true, true);

if ($type == 1) { // Type 1: is for transcript
    $filename = format_string($moduleinstance->name) . '_transcript';
    $mimetype = 'text/plain';
}

$exportfile = new export_file($filename, $mimetype);
$exportfile->send_header();
echo $transcript;
exit;
