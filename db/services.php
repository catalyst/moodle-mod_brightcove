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
 * Brightcove Activity web service external functions and service definitions.
 *
 * @package    mod_brightcove
 * @copyright  2017 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
    'mod_brightcove_video_list' => array(
        'classname'   => 'mod_brightcove_external',
        'methodname'  => 'video_list',
        'classpath'   => 'mod/brightcove/externallib.php',
        'description' => 'Returns available videos via the Brightcove API',
        'type'        => 'read',
        'capabilities'  => 'mod/brightcove:addinstance',
        'ajax' => true
    ),
    'mod_brightcove_video' => array(
        'classname'   => 'mod_brightcove_external',
        'methodname'  => 'video',
        'classpath'   => 'mod/brightcove/externallib.php',
        'description' => 'Returns video via the Brightcove API',
        'type'        => 'read',
        'capabilities'  => 'mod/brightcove:addinstance',
        'ajax' => true
    ),
    'mod_brightcove_get_user_progress' => array(
        'classname'     => 'mod_brightcove_external',
        'methodname'    => 'get_user_progress',
        'classpath'     => 'mod/brightcove/externallib.php',
        'description'   => 'Get user activity progress record.',
        'type'          => 'read',
        'ajax'          => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_brightcove_update_user_progress' => array(
        'classname'     => 'mod_brightcove_external',
        'methodname'    => 'update_user_progress',
        'classpath'     => 'mod/brightcove/externallib.php',
        'description'   => 'Create/Update user activity progress record.',
        'type'          => 'write',
        'ajax'          => true,
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Brightcove service' => array(
        'functions' => array('mod_brightcove_video_list', 'mod_brightcove_video'),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
