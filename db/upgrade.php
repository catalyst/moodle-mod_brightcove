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
 * Upgrade code for the brightcove activity
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_brightcove\brightcove_api;

defined('MOODLE_INTERNAL') || die();

function xmldb_brightcove_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017080901) {

        // Define field transcript to be dropped from brightcove.
        $table = new xmldb_table('brightcove');
        $field = new xmldb_field('transcript');

        // Conditionally launch drop field transcript.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Brightcove savepoint reached.
        upgrade_mod_savepoint(true, 2017080901, 'brightcove');
    }

    if ($oldversion < 2017081400) {
        $instances = $DB->get_records('brightcove');

        // Create local transcript files for all brightcove instances.
        foreach ($instances as $instance) {
            $cm = get_coursemodule_from_instance('brightcove', $instance->id);
            $context = context_module::instance($cm->id);

            $brightcove = new brightcove_api($instance, $context);
            $brightcove->save_transcript();
        }

        // Brightcove savepoint reached.
        upgrade_mod_savepoint(true, 2017081400, 'brightcove');
    }

    return true;
}
