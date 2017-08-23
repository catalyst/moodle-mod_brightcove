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
 * Brightcove Activity Web Service
 *
 * @package    mod_brightcove
 * @copyright  2017 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

/**
 * Brightcove Activity Web Service
 *
 * @package    mod_brightcove
 * @copyright  2017 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_brightcove_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function video_list_parameters() {
        return new external_function_parameters(
                array(
                        // If I had any parameters, they would be described here. But I don't have any, so this array is empty.
                )
            );
    }

    /**
     * Returns available videos
     *
     */
    public static function video_list() {
        global $USER;

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('mod/brightcove:addinstance', $context)) {
            throw new moodle_exception('cannot_access_api');
        }

        // Execute API call.
        $brightcove = new \mod_brightcove\brightcove_api();
        $results = $brightcove->get_video_list();

        return $results;

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function video_list_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_TEXT, 'Brightcove video ID'),
                        'name' => new external_value(PARAM_TEXT, 'Video title'),
                        'complete' => new external_value(PARAM_RAW, 'whether processing is complete'),
                        'created_at' => new external_value(PARAM_TEXT, 'when the video was created'),
                        'duration' => new external_value(PARAM_INT, 'video duration in milliseconds'),
                        'thumbnail_url' => new external_value(PARAM_RAW, 'URL for the default thumbnail source image'),
                        )
                    )
                );
    }

}