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
 * @package     brightcove
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
function brightcove_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        default:
            return null;
    }
}

/**
 * Returns if brightcove plugin has valid
 * configuration at site level.
 *
 * @return boolean
 */
function brightcove_is_configured() {
    $isconfigured = true;
    $moduleconfig = get_config('brightcove');

    if ($moduleconfig->accountid == ''
            || $moduleconfig->playerid == ''
            || $moduleconfig->apikey == ''
            || $moduleconfig->apisecret == ''
            || $moduleconfig->oauthendpoint == ''
            || $moduleconfig->apiendpoint == '') {
                $isconfigured= false;
            }

    return $isconfigured;
}

/**
 * Saves a new instance of the brightcove into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param brightcove_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function brightcove_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $cmid = $moduleinstance->coursemodule;
    $context = context_module::instance($cmid);

    $moduleinstance->timecreated = time();

    $brightcove = new brightcove_api();
    $brightcove->set_context($context);
    $brightcove->save_transcript();

    $id = $DB->insert_record('brightcove', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the brightcove in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param brightcove_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function brightcove_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $cmid = $moduleinstance->coursemodule;
    $context = context_module::instance($cmid);

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    $brightcove = new brightcove_api();
    $brightcove->set_context($context);
    $brightcove->save_transcript($moduleinstance->videoid);

    return $DB->update_record('brightcove', $moduleinstance);
}

/**
 * Removes an instance of the brightcove from the database.
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

    $brightcove = new brightcove_api();
    $brightcove->set_context($context);
    $brightcove->delete_transcript($moduleinstance->videoid);

    $DB->delete_records('brightcove', array('id' => $id));

    return true;
}


function brightcove_coursemodule_validation(moodleform_mod $modform, array $data) {
    return array();
}

/**
 * Extends the global navigation tree by adding brightcove nodes if there is a relevant content.
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
 * Extends the settings navigation with the brightcove settings.
 *
 * This function is called when the context for the page is a brightcove module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $brightcovenode {@link navigation_node}
 */
function brightcove_extend_settings_navigation($settingsnav, $brightcovenode = null) {
}

/**
 * Checks if scale is being used by any instance of brightcove.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any brightcove instance.
 */
function brightcove_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('brightcove', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given brightcove instance.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function brightcove_grade_item_update($moduleinstance, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('/mod/brightcove', $moduleinstance->course, 'mod', 'brightcove', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given brightcove instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function brightcove_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('/mod/brightcove', $moduleinstance->course, 'mod', 'brightcove',
        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update brightcove grades in the gradebook.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function brightcove_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('/mod/brightcove', $moduleinstance->course, 'mod', 'brightcove', $moduleinstance->id, 0, $grades);
}
