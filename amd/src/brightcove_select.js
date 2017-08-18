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
 * @package     mod/brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module      mod_brightcove/brightcove_select
 */
define(['jquery', 'core/str', 'core/modal_factory'], function ($, Str, ModalFactory) {

    var BrightcoveSelect = {};

    /**
     * Initialise the class.
     *
     * @param {videoid} selector used to find triggers for the new group modal.
     * @private
     */
    BrightcoveSelect.init = function(videoid) {
        var trigger = $('#id_brightcove_modal'); // form button to trigger modal

        //Get the Title String
        Str.get_string('modaltitle', 'mod_brightcove').then(function(title) {
            // Create the Modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: '<p>test body content</p>'
            }, trigger)
            .done(function(modal) {
                modal.setBody('foo');
            });
        });


    };
 
    return BrightcoveSelect;
});