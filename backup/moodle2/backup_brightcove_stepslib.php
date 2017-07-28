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

defined('MOODLE_INTERNAL') || die;

/**
 * Define all the backup steps that will be used by the backup_brightcove_activity_task
 */
class backup_brightcove_activity_structure_step extends backup_activity_structure_step {

    /**
     * Backup structure step.
     *
     * @return \backup_nested_element
     *
     * @throws \base_element_struct_exception
     * @throws \base_step_exception
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $brightcove = new backup_nested_element('brightcove', array('id'), array(
            'name', 'intro', 'introformat', 'videoid', 'aspectratio',
            'transcript', 'videoname', 'timemodified'));

        $brightcove->set_source_table('brightcove', array('id' => backup::VAR_ACTIVITYID));
        $brightcove->annotate_files('mod_brightcove', 'intro', null);

        return $this->prepare_activity_structure($brightcove);
    }
}
