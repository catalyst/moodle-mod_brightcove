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

use mod_brightcove\progress;

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
     * Validate provided course module.
     *
     * @param int $cmid Course module ID.
     *
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    protected static function validate_course_module($cmid, $capability) {
        global $DB;

        if (!$DB->record_exists('course_modules', array('id' => $cmid))) {
            throw new invalid_parameter_exception('Course module record not found');
        }

        $context = context_module::instance($cmid);
        require_capability($capability, $context);
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function video_list_parameters() {
        return new external_function_parameters(
                array(
                        'q' => new external_value(PARAM_TEXT, 'The search query', VALUE_DEFAULT, '*'),
                        'page' => new external_value(PARAM_INT,
                                'Page number of results to return', VALUE_DEFAULT, 1),
                )
            );
    }

    /**
     * Returns available videos
     *
     */
    public static function video_list($q, $page) {
        global $USER;

        // Parameter validation.
        // This feels dumb and the docs are vague, buy it is required.
        $params = self::validate_parameters(self::video_list_parameters(),
                array('q' => $q,
                      'page' => $page,
                ));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('mod/brightcove:addinstance', $context)) {
            throw new moodle_exception('cannot_access_api');
        }

        // Execute API call.
        $brightcove = new \mod_brightcove\brightcove_api();
        $results = $brightcove->get_video_list($q, $page);

        return $results;

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function video_list_returns() {
        return new external_single_structure(
            array(
                'videos' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_TEXT, 'Brightcove video ID'),
                            'name' => new external_value(PARAM_TEXT, 'Video title'),
                            'complete' => new external_value(PARAM_TEXT, 'whether processing is complete'),
                            'created_at' => new external_value(PARAM_TEXT, 'when the video was created'),
                            'duration' => new external_value(PARAM_TEXT, 'video duration'),
                            'thumbnail_url' => new external_value(PARAM_RAW, 'URL for the default thumbnail source image'),
                        )
                    )
                ),
                'pages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'page' => new external_value(PARAM_INT, 'page'),
                        )
                    )
                ),
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function video_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'The Brightcove video ID', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Returns video
     *
     */
    public static function video($id) {
        global $USER;

        // Parameter validation.
        // This feels dumb and the docs are vague, buy it is required.
        $params = self::validate_parameters(self::video_parameters(),
            array('id' => $id));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability checking.
        if (!has_capability('mod/brightcove:addinstance', $context)) {
            throw new moodle_exception('cannot_access_api');
        }

        // Execute API call.
        $brightcove = new \mod_brightcove\brightcove_api();
        $results = $brightcove->get_video_by_id($id);

        return $results;

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function video_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_TEXT, 'Brightcove video ID'),
                'name' => new external_value(PARAM_TEXT, 'Video title'),
                'complete' => new external_value(PARAM_TEXT, 'whether processing is complete'),
                'created_at' => new external_value(PARAM_TEXT, 'when the video was created'),
                'duration' => new external_value(PARAM_TEXT, 'video duration'),
                'thumbnail_url' => new external_value(PARAM_RAW, 'URL for the default thumbnail source image'),
             )
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return \external_single_structure
     */
    public static function get_user_progress_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
                'userid' => new external_value(PARAM_INT, 'User id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Get user progress.
     *
     * @param $cmid
     * @param $userid
     * @return array
     */
    public static function get_user_progress($cmid, $userid) {
        $params = self::validate_parameters(self::get_user_progress_parameters(), compact('cmid', 'userid'));

        self::validate_course_module($params['cmid'], 'mod/brightcove:view_progress');

        $progress = new progress($params);

        return (array) $progress->get_record_data();
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_single_structure
     */
    public static function get_user_progress_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Record ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage from 0 to 100'),
            'duration' => new external_value(PARAM_INT, 'Duration in seconds'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
        ));
    }

    /**
     * Returns description of method parameters.
     *
     * @return \external_single_structure
     */
    public static function update_user_progress_parameters() {
        return new external_function_parameters(
            array(
                'progress' => new external_single_structure(
                    array(
                        'cmid' => new external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
                        'userid' => new external_value(PARAM_INT, 'User id', VALUE_REQUIRED),
                        'progress' => new external_value(PARAM_FLOAT, 'Progress percentage from 0 to 100', VALUE_OPTIONAL),
                        'duration' => new external_value(PARAM_INT, 'User duration', VALUE_OPTIONAL)
                    )
                )
            )
        );
    }

    /**
     * Update user progress.
     *
     * @param array $data
     *
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function update_user_progress($data) {
        $params = self::validate_parameters(self::update_user_progress_parameters(), ['progress' => $data]);

        self::validate_course_module($params['progress']['cmid'], 'mod/brightcove:update_progress');

        try {
            $progress = new progress($params['progress']);

            if (!empty($progress->id)) {
                $progress->update();
            } else {
                $progress->insert();
            }
        } catch (Exception $e) {
            throw new invalid_parameter_exception($e->getMessage());
        }

        return (array) $progress->get_record_data();
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_single_structure
     */
    public static function update_user_progress_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Record ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage from 0 to 100'),
            'duration' => new external_value(PARAM_INT, 'Duration in seconds'),
            'timecreated' => new external_value(PARAM_INT, 'Time created'),
            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
        ));
    }
}
