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

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function video_list_returns() {

    }

}