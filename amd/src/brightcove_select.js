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
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/templates', 'core/ajax'],
        function ($, Str, ModalFactory, ModalEvents, Templates, ajax) {

    /**
     * Module level variables.
     */
    var BrightcoveSelect = {};
    var modalObj;
    var page = 1;
    var q = '';
    var videosObj;
    var selected = {};
    var spinner = '<p class="text-center"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i><span class="sr-only">Loading...</span></p>';

    /**
     * Add click handlers to dynamically create
     * page and modal elements.
     *
     * @private
     */
    function clickHandlers() {
        // Click hander for video select.
        $('body').on('click', '.bc-sl-d-container.row', function() {
            $(this).addClass( "selected" ).siblings().removeClass('selected');
            var videoId = $(this).data("video-id");
            var video = videosObj.filter(function( obj ) {
                return obj.id == videoId;
              });
            selected = video[0]

        });

        // Click handler for paging.
        $('body').on('click', 'a.page-link', function() {
            event.preventDefault();
            page = $(this).data("page-id");
            updateBody();
        });

        // Click handlers for search.
        $('body').on('click', "[name='bc_search_go']", function() {
            event.preventDefault();
            search($("[name='bc_search']").val());
        });

        $('body').on('keypress', "[name='bc_search']", function() {
            if (event.key == 'Enter') {
                event.preventDefault();
                search($("[name='bc_search']").val());
            }
        });
    }

    /**
     * Perform search of videos.
     *
     * @param query
     * @private
     */
    function search(query) {
        if (query !== '') {
            q = query;
            updateBody();
        }
    }

    /**
     * Updates the body of the modal window,
     * when a paging event is triggered.
     *
     * @private
     */
    function updateBody() {
        modalObj.setBody(spinner);
        var promises = ajax.call([
            { methodname: 'mod_brightcove_video_list', args: {q: q, page: page} },
        ]);

       promises[0].done(function(response) {
           videosObj = response.videos;
           modalObj.setBody(Templates.render('mod_brightcove/video_list', {videos : response.videos, pages: response.pages, q: q}));

       }).fail(function(ex) {
           // do something with the exception
       });
    }

    /**
     * Add video details to Moodle form if we are editing
     * an existing activity.
     *
     * @param videoId
     * @private
     */
    function initForm(videoId) {
        $('#bc-selected-video').html(spinner);
        var promises = ajax.call([
            { methodname: 'mod_brightcove_video', args: {id: videoId} },
        ]);

       promises[0].done(function(response) {
           selected = response
           Templates.render('mod_brightcove/video_list_form', selected).done(function(RenderResp) {
               $('#bc-selected-video').html(RenderResp);
           });

       }).fail(function(ex) {
           // do something with the exception
       });
    }

    /**
     * Updates Moodle form with slected video information.
     * @private
     */
    function updateForm() {
        $("[name='videoid']").val(selected.id);
        Templates.render('mod_brightcove/video_list_form', selected).done(function(response) {
            $('#bc-selected-video').html(response);
        });
    }

    /**
     * Initialise the class.
     *
     * @param {videoid} selector used to find triggers for the new group modal.
     * @public
     */
    BrightcoveSelect.init = function(videoid) {
        var trigger = $('#id_brightcove_modal'); // form button to trigger modal
        var videoId = $("[name='videoid']").val();

        if (videoId !== '') {
           initForm(videoId);
        }


        //Get the Title String
        Str.get_string('modaltitle', 'mod_brightcove').then(function(title) {
            // Create the Modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: spinner,
                large: true
            }, trigger)
            .done(function(modal) {
                modalObj = modal;
                modalObj.getRoot().on(ModalEvents.save, updateForm);
                clickHandlers();
                updateBody();
            });
        });
    };
 
    return BrightcoveSelect;
});
