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
 * Plugin administration pages are defined here.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Brightcove API and account settiings.
    $settings->add(new admin_setting_configtext('brightcove/accountid',
            get_string('accountid',             'brightcove'),
            get_string('accountid_help',        'brightcove'),
            null, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('brightcove/playerid',
            get_string('playerid',             'brightcove'),
            get_string('playerid_help',        'brightcove'),
            null, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('brightcove/apikey',
            get_string('apikey',      'brightcove'),
            get_string('apikey_help', 'brightcove'),
            null, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('brightcove/apisecret',
            get_string('apisecret',      'brightcove'),
            get_string('apisecret_help', 'brightcove'),
            null, PARAM_TEXT));

}
