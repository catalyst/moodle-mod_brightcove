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
 * Backup/Restore.
 *
 * @package     mod_brightcove
 * @copyright   2017 Dmitrii Metelkin <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_brightcove_activity_task
 */

/**
 * Structure step to restore one brightcove activity
 */
class restore_brightcove_activity_structure_step extends restore_activity_structure_step {

    /**
     * Restore structure definition.
     * @return array
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('brightcove', '/activity/brightcove');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process brightcove restore step.
     *
     * @param $data
     *
     * @throws \base_step_exception
     */
    protected function process_brightcove($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('brightcove', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * After execution step.
     */
    protected function after_execute() {
        $this->add_related_files('mod_brightcove', 'intro', null);
    }
}
