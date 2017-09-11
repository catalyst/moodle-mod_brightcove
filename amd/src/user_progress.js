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
 * @package     mod_brightcove
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_brightcove/user_progress
 */
define(['jquery', 'core/ajax'], function ($, ajax) {
    function UserProgress() {
        this.cmid = null;
        this.userid = null;
        this.shouldSave = false;
        this.debounceTimer = null;
        this.debounceIntervalMS = 1000;
        this.currentProgress = null;
        this.ajaxInProgressCount = 0;
        this.elementCurrent = null;
        this.elementMaximum = null;
        this.maximumProgress = null;

        this.listeners = {
            'received': [],
            'updated': []
        };

    }

    UserProgress.prototype.initialise = function (cmid, userid, maximumProgress) {
        this.cmid = cmid;
        this.userid = userid;
        this.maximumProgress = maximumProgress;

        this.addListener('received', this.received.bind(this));
        this.dispatchUpdatedEvent();
    };

    UserProgress.prototype.received = function (event) {
        this.ajaxInProgressCount = Math.max(0, this.ajaxInProgressCount - 1);
        this.dispatchUpdatedEvent();

        this.maximumProgress = Math.max(this.maximumProgress, event.detail.progress);
        this.dispatchUpdatedEvent();
    };

    UserProgress.prototype.addListener = function (event, listener) {
        this.listeners[event].push(listener);
    };

    UserProgress.prototype.dispatchEvent = function (event) {
        this.listeners[event.type].forEach(function (listener) {
            listener(event);
        });
    };

    UserProgress.prototype.get = function () {
        var args = {
            cmid: this.cmid,
            userid: this.userid
        };
        var promise = ajax.call([{
            methodname: 'mod_brightcove_get_user_progress',
            args: args
        }])[0];
        this.ajaxInProgressCount++;

        var self = this;
        promise.fail(function (response) {
            window.console.error('UserProgress.get.fail', response);
        });
        promise.done(function (response) {
            self.dispatchEvent(new CustomEvent('received', {'detail': response}));
        });
    };

    UserProgress.prototype.update = function (currentProgress) {
        this.currentProgress = currentProgress;

        if ((this.maximumProgress === null) || (this.maximumProgress < currentProgress)) {
            this.maximumProgress = currentProgress;
            this.shouldSave = true;
            this.save();
        }

        this.dispatchUpdatedEvent();
    };

    UserProgress.prototype.forceUpdate = function (progress) {
        this.currentProgress = progress;
        this.maximumProgress = progress;
        this.shouldSave = true;
        this.save();
        this.dispatchUpdatedEvent();
    };

    UserProgress.prototype.dispatchUpdatedEvent = function () {
        this.dispatchEvent(new CustomEvent('updated', {'detail': this}));
    };

    UserProgress.prototype.save = function () {
        if (!this.shouldSave) {
            return;
        }

        // Debounce timer, but ignore it when reaching 100%.
        if (this.debounceTimer && (this.maximumProgress < 100)) {
            return;
        }

        this.saveAjax();

        if (this.debounceIntervalMS > 0) {
            this.debounceTimer = setTimeout(this.debounceTrigger.bind(this), this.debounceIntervalMS);
        }

        this.dispatchUpdatedEvent();
    };

    UserProgress.prototype.saveNow = function () {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }

        this.save();
    };

    UserProgress.prototype.debounceTrigger = function () {
        this.debounceTimer = null;
        this.save();
    };

    UserProgress.prototype.saveAjax = function () {
        this.shouldSave = false;
        var args = {
            progress: {
                cmid: this.cmid,
                userid: this.userid,
                progress: this.maximumProgress
            }
        };
        var promise = ajax.call([{
            methodname: 'mod_brightcove_update_user_progress',
            args: args
        }])[0];
        this.ajaxInProgressCount++;

        var self = this;
        promise.fail(function (response) {
            window.console.error('UserProgress.saveAjax.fail', response);
        });
        promise.done(function (response) {
            self.dispatchEvent(new CustomEvent('received', {'detail': response}));
        });
    };

    return new UserProgress();
});
