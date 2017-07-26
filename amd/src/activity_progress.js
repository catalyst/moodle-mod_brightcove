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
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module      mod_brightcove/activity_progress
 */
define(['jquery', 'local_activity_progress/user_progress', 'bc'], function ($, userProgress) {
    /* global videojs */

    function BrightcoveProgress() {
        this.playerid = null;
        this.player = null;
        this.userProgressAPI = userProgress;
        this.updateIntervalMS = 1000 / 24;
        this.intervalid = null;
    }

    BrightcoveProgress.prototype.onLoadedmetadata = function () {
        var progressPercent = this.player.el().dataset.progress;
        var totalDuration = this.player.duration();
        var playedDuration = (totalDuration * (progressPercent / 100)).toFixed(3);
        var startPosition = 0;

        if (progressPercent > 98) {
            startPosition = 0;
        } else {
            startPosition = playedDuration;
        }

        $('#' + this.playerid).removeClass('notHover vjs-paused ').addClass('vjs-has-started').each(function(){
            if (startPosition == 0) {
                $('.vjs-poster').show();
            }
        });
        $('.vjs-big-play-button').show();
        $('.vjs-play-control').focus();

        this.player.currentTime(startPosition);
        this.startMonitoring();
    };

    BrightcoveProgress.prototype.onPlay = function () {
        $('.vjs-big-play-button').hide();
        $('.vjs-poster').hide();
    };

    BrightcoveProgress.prototype.onPause = function () {
        $('.vjs-big-play-button').show();
        this.userProgressAPI.saveNow();
    };

    BrightcoveProgress.prototype.onEnded = function () {
        this.userProgressAPI.update(100);
    };

    BrightcoveProgress.prototype.stopMonitoring = function () {
        if (this.intervalid !== null) {
            this.player.clearInterval(this.intervalid);
            this.intervalid = null;
        }
    };

    BrightcoveProgress.prototype.startMonitoring = function () {
        this.stopMonitoring();
        this.intervalid = this.player.setInterval(this.updateProgress.bind(this), this.updateIntervalMS);
    };

    BrightcoveProgress.prototype.updateProgress = function () {
        var current = this.player.currentTime();
        var duration = this.player.duration();
        var percentage = current / duration * 100;

        if (!isNaN(percentage)) {
            this.userProgressAPI.update(percentage);
        }
    };

    BrightcoveProgress.prototype.init = function (parameters) {
        this.playerid = parameters.playerid;
        this.player = videojs(this.playerid);
        this.userProgressAPI.initialise(parameters.cmid, parameters.userid);

        this.player.on('play', this.onPlay.bind(this));
        this.player.on('pause', this.onPause.bind(this));
        this.player.on('ended', this.onEnded.bind(this));
        this.player.on('loadedmetadata', this.onLoadedmetadata.bind(this));
    };

    return new BrightcoveProgress();
});
