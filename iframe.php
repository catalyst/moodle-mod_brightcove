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
use mod_brightcove\progress;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot . '/mod/brightcove/externallib.php');

// Course_module ID, or
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
$activityobject = mod_brightcove_external::get_user_progress($cm->id, $USER->id);

// Check if we have a configured Brightcove instance
$pluginconfigured = true;
if ($moduleconfig->accountid == ''
        || $moduleconfig->playerid == ''
        || $moduleconfig->apikey == ''
        || $moduleconfig->apisecret == ''
        || $moduleconfig->oauthendpoint == ''
        || $moduleconfig->apiendpoint == '') {
            $pluginconfigured = false;
        }

$PAGE->set_url('/mod/brightcove/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('embedded');

$brightcove = new brightcove_api();
$brightcoveurl = $brightcove->get_videoplayer_js_url();

$player = new stdClass();
$player->accountid = $moduleconfig->accountid;
$player->playerid = $moduleconfig->playerid;
$player->videoid = $moduleinstance->videoid;
$player->progress = $activityobject['progress'];

$PAGE->requires->js_amd_inline("requirejs.config({paths:{'bc':['{$brightcoveurl}']}});");
$PAGE->requires->js_call_amd('mod_brightcove/brightcove', 'init', array($moduleconfig->playerid));
$PAGE->requires->js_call_amd('mod_brightcove/activity_progress', 'init', [
    [
        'playerid' => $player->playerid,
        'cmid'     => $cm->id,
        'userid'   => $USER->id,
        'maximumProgress' => $activityobject['progress'],
    ],
]);

$output = $PAGE->get_renderer('mod_brightcove');

echo $output->header();
echo $OUTPUT->render_from_template('mod_brightcove/player', $player);
echo $output->footer();
