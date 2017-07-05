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
 * The main mod_brightcove configuration form.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_brightcove
 * @copyright  2017 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_brightcove_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('brightcovename', 'mod_brightcove'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'brightcovename', 'mod_brightcove');

        $this->standard_intro_elements();

        // Brightcove video ID.
        $mform->addElement('text', 'videoid', get_string('videoid', 'brightcove'), array('size'=>'64'));
        $mform->addRule('videoid', null, 'required', null, 'client');
        $mform->setType('videoid', PARAM_TEXT);
        $mform->addHelpButton('videoid', 'videoid', 'mod_brightcove');

        // Video Aspect ratio.
        // When proper API integration has been added this will be removed,
        // as we will source this infor directly from the API.
        $options = array(
                169 => get_string('aspect169', 'brightcove'),
                43 => get_string('aspect43', 'brightcove')
        );
        $select = $mform->addElement('select', 'aspectratio', get_string('aspectratio', 'brightcove'), $options);
        // This will select the colour blue.
        $select->setSelected(169);

        $mform->addHelpButton('aspectratio', 'aspectratio', 'mod_brightcove');

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
