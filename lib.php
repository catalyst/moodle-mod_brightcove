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
 * Library of interface functions and constants.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_brightcove\brightcove_api;

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function mod_brightcove_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_brightcove into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_brightcove_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function brightcove_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $cmid = $moduleinstance->coursemodule;
    $context = context_module::instance($cmid);

    $brightcove = new brightcove_api($moduleinstance, $context);
    $brightcove->save_transcript();

    $moduleinstance->timecreated = time();
    $id = $DB->insert_record('brightcove', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_brightcove in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_brightcove_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function brightcove_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $cmid = $moduleinstance->coursemodule;
    $context = context_module::instance($cmid);

    $brightcove = new brightcove_api($moduleinstance, $context);
    $brightcove->save_transcript();

    $moduleinstance->timecreated = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('brightcove', $moduleinstance);
}

/**
 * Removes an instance of the mod_brightcove from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function brightcove_delete_instance($id) {
    global $DB;

    $moduleinstance = $DB->get_record('brightcove', array('id' => $id));
    if (!$moduleinstance) {
        return false;
    }

    $cm = get_coursemodule_from_instance('brightcove', $moduleinstance->id);
    $context = context_module::instance($cm->id);

    $brightcove = new brightcove_api($moduleinstance, $context);
    $brightcove->delete_transcript();

    $DB->delete_records('brightcove', array('id' => $id));

    return true;
}


function brightcove_coursemodule_validation(moodleform_mod $modform, array $data) {
    return array();
}

/**
 * Extends the global navigation tree by adding mod_brightcove nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $brightcovenode An object representing the navigation tree node.
 * @param stdClass $course course.
 * @param stdClass $module module.
 * @param cm_info $cm cm.
 */
function brightcove_extend_navigation($brightcovenode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_brightcove settings.
 *
 * This function is called when the context for the page is a mod_brightcove module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $brightcovenode {@link navigation_node}
 */
function brightcove_extend_settings_navigation($settingsnav, $brightcovenode = null) {
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function brightcove_get_file_areas($course, $cm, $context) {
    return array(
        'transcript' => get_string('filearea_transcript', 'brightcove'),
    );
}

/**
 * File browsing support.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 *
 * @return file_info instance or null if not found
 */
function brightcove_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    if (!has_capability('moodle/course:managefiles', $context)) {
        return null;
    }

    if ($filearea === 'transcript' ) {
        $fs = get_file_storage();

        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        if (!$storedfile = $fs->get_file($context->id, 'mod_brightcove', $filearea, 0, $filepath, $filename)) {
            return null;
        }

        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, true);
    }

    return null;
}

/**
 * Serves the files.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param context $context the module context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 *
 * @return null script execution stopped unless $options['dontdie'] is true
 */
function brightcove_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    if ($filearea !== 'transcript') {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim('/' . $context->id . '/mod_brightcove/' . $filearea . '/' . $relativepath, '/');

    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}
