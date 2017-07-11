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
 * Prints an instance of mod_brightcove.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_brightcove\brightcove_api;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID, or.
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$b  = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('brightcove', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('brightcove', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($b) {
    $moduleinstance = $DB->get_record('brightcove', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('brightcove', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', mod_brightcove));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
$moduleconfig = get_config('brightcove');

// Calulate video aspect ratio.
$aspectratio = "56.25";
if ($moduleinstance->aspectratio != 169) {
    $aspectratio = "75";
}

$brightcove = new brightcove_api();
$video_transcript = $brightcove->get_transcript($moduleinstance->videoid);

$playervalues = new stdClass();
$playervalues->accountid = $moduleconfig->accountid;
$playervalues->playerid = $moduleconfig->playerid;
$playervalues->videoid = $moduleinstance->videoid;
$playervalues->aspectratio = $aspectratio;
$playervalues->transcripturl = $video_transcript['src'];

$PAGE->set_url('/mod/brightcove/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$PAGE->requires->js(new moodle_url('https://players.brightcove.net/'.$moduleconfig->accountid.'/'.$moduleconfig->playerid.'_default/index.js'));
$PAGE->requires->js_call_amd('mod_brightcove/brightcove', 'init', array($moduleconfig->playerid));
$PAGE->requires->js_call_amd('mod_brightcove/videojs_transcript', 'init');
$PAGE->requires->js_call_amd('mod_brightcove/mark_transcript', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_brightcove/player', $playervalues);
echo $OUTPUT->footer();
