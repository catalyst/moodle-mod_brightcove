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
 * Javascript controller for the "Actions" panel at the bottom of the page.
 *
 * @module     mod_brightcove/download_video
 * @package    mod_brightcove
 * @class      MarkTranscript
 * @copyright  2017 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['bc'], function() {

    var Brightcove = {};

    Brightcove.init = function (playerid){
        // Construct the download video plugin.
        function constructDownloadPlugin(playerid) {
            videojs.registerPlugin('downloadVideoPlugin', function() {

                // Create variables and new div, anchor and image for download icon.
                var myPlayer = this,
                    videoName,
                    totalRenditions,
                    mp4Ara = [],
                    highestQuality;

                myPlayer.on('loadstart',function(){

                    // Reinitialize array of MP4 renditions in case used with playlist.
                    // This prevents the array having a cumulative list for all videos in playlist.
                    mp4Ara = [];
                    // Get video name and the MP4 renditions.
                    videoName = myPlayer.mediainfo['name'];
                    rendtionsAra = myPlayer.mediainfo.sources;
                    totalRenditions = rendtionsAra.length;
                    for (var i = 0; i < totalRenditions; i++) {
                        if (rendtionsAra[i].container === "MP4" && rendtionsAra[i].hasOwnProperty('src')) {
                            mp4Ara.push(rendtionsAra[i]);
                        };
                    };
                    // Sort the renditions from highest to lowest.
                    mp4Ara.sort( function (a,b){
                        return b.size - a.size;
                    });
                    // Set the highest rendition.
                    highestQuality = mp4Ara[0].src;

                    parent.postMessage(highestQuality, '*');
                });
            });
        }

        // Call the plugin constructors.
        constructDownloadPlugin(playerid);

        // Attach the plugins to videoJS.
        videojs(playerid).downloadVideoPlugin();
    };

    return Brightcove;
});
