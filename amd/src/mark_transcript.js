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
define(['jquery', 'mod_brightcove/jquery.mark'], function($, Mark) {

    var MarkTranscript = {};

    MarkTranscript.init = function (){
        // the input field
        var $input = $("input[type='search']"),
          // clear button
          $clearBtn = $("button[data-search='clear']"),
          // prev button
          $prevBtn = $("button[data-search='prev']"),
          // next button
          $nextBtn = $("button[data-search='next']"),
          // the context where to search
          $content = $("#transcript"),
          // jQuery object to save <mark> elements
          $results,
          // the class that will be appended to the current
          // focused element
          currentClass = "current",
          // top offset for the jump (the search bar)
          offsetTop = 50,
          // the current index of the focused element
          currentIndex = 0;

        /**
         * Jumps to the element matching the currentIndex
         */
        function jumpTo() {
          if ($results.length) {
            var position,
              $current = $results.eq(currentIndex);
            $results.removeClass(currentClass);
            if ($current.length) {
              $current.addClass(currentClass);
              position = $current.offset().top - offsetTop;
              window.scrollTo(0, position);
            }
          }
        }

        /**
         * Searches for the entered keyword in the
         * specified context on input
         */
        $input.on("input", function() {
          var searchVal = this.value;
          $content.unmark({
            done: function() {
              $content.mark(searchVal, {
                separateWordSearch: true,
                done: function() {
                  $results = $content.find("mark");
                  currentIndex = 0;
                  jumpTo();
                }
              });
            }
          });
        });

        /**
         * Clears the search
         */
        $clearBtn.on("click", function() {
          $content.unmark();
          $input.val("").focus();
        });

        /**
         * Next and previous search jump to
         */
        $nextBtn.add($prevBtn).on("click", function() {
          if ($results.length) {
            currentIndex += $(this).is($prevBtn) ? -1 : 1;
            if (currentIndex < 0) {
              currentIndex = $results.length - 1;
            }
            if (currentIndex > $results.length - 1) {
              currentIndex = 0;
            }
            jumpTo();
          }
        });
    };

    return MarkTranscript;
});
