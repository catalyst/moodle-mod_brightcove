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
 * Progress manager.
 *
 * @package   mod_brightcove
 * @author    Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_brightcove;

use coding_exception;
use data_object;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/completion/data_object.php');

/**
 * Class progress to manage records in mod_brightcove.
 * @package mod_brightcove
 */
class progress extends data_object {

    /**
     * Table that the class maps to in the database
     *
     * @var string
     */
    public $table = 'brightcove_progress';

    /**
     * Array of required table fields, must start with 'id'.
     *
     * @var array
     */
    public $required_fields = array(
        'id',
        'cmid',
        'userid',
    );

    /**
     * Array of optional fields with default values - usually long text information that is not always needed.
     *
     * @var array
     */
    public $optional_fields = array(
        'timecreated' => 0,
        'timemodified' => 0,
        'duration' => 0,
        'progress' => 0,
    );

    /**
     * Array of unique fields, used in where clauses and constructor.
     *
     * @var array
     */
    public $unique_fields = array(
        'cmid',
        'userid',
    );

    /**
     * Course module ID.
     *
     * @var int
     */
    public $cmid;

    /**
     * User ID.
     *
     * @var int
     */
    public $userid;

    /**
     * Time created.
     *
     * @var int
     */
    public $timecreated;

    /**
     * Time modified.
     *
     * @var int
     */
    public $timemodified;

    /**
     * User duration on the activity in seconds.
     *
     * @var int
     */
    public $duration;

    /**
     * User progress percentage.
     *
     * @var int
     */
    public $progress;


    /**
     * Constructor.
     *
     * @param array| null $params Parameters and their values for this data object.
     *
     * @throws coding_exception If incorrect data provided.
     */
    public function __construct($params = null) {

        foreach ($this->required_fields as $name) {
            if (isset($params[$name]) && !is_number($params[$name])) {
                throw new coding_exception("$name should be numeric!");
            }
        }

        foreach ($this->optional_fields as $name => $defaultvalue) {
            if ($name == 'progress') {
                if (isset($params[$name]) && !is_numeric($params[$name])) {
                    throw new coding_exception("$name should be numeric!");
                }
            } else {
                if (isset($params[$name]) && !is_number($params[$name])) {
                    throw new coding_exception("$name should be numeric!");
                }
            }
        }

        parent::__construct($params, DATA_OBJECT_FETCH_BY_KEY);
    }


    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname = >value
     * @return data_object instance of data_object or false if none found.
     *
     * @throws coding_exception
     */
    public static function fetch($params) {
        return self::fetch_helper(self::get_table(), __CLASS__, $params);
    }

    /**
     * Insert object in DB.
     *
     * @return int
     * @throws \coding_exception
     */
    public function insert() {
        $this->validate();
        $this->normalise_fields();

        $this->timecreated = time();
        $this->timemodified = time();

        return parent::insert();
    }

    /**
     * Update object in DB.
     *
     * @return bool
     * @throws \coding_exception
     */
    public function update() {
        $this->validate();
        $this->normalise_fields();

        $this->timemodified = time();

        return parent::update();
    }

    /**
     * Validate before insert or update in DB.
     *
     * @throws \coding_exception
     */
    protected function validate() {
        if (empty($this->userid) || !is_number($this->userid)) {
            throw new coding_exception("User id should be numeric!");
        }

        if (empty($this->cmid) || !is_number($this->cmid)) {
            throw new coding_exception("Course module id should be numeric!");
        }

        if (!is_numeric($this->progress)) {
            throw new coding_exception("Progress should be numeric!");
        }

        if (!is_number($this->duration)) {
            throw new coding_exception("Duration should be numeric!");
        }
    }

    /**
     * Normalise fields values.
     */
    protected function normalise_fields() {
        if ($this->progress > 100) {
            $this->progress = 100;
        }

        if ($this->progress < 0) {
            $this->progress = 0;
        }
    }

    /**
     * Helper function to get table name.
     *
     * @return string
     */
    protected static function get_table() {
        return self::get_instance()->table;
    }

    /**
     * Helper function to get an instance of the class.
     *
     * @return mixed
     */
    protected static function get_instance() {
        $classname = __CLASS__;
        $instance = new $classname();

        return $instance;
    }

    /**
     * Bulk delete records in DB.
     *
     * @param array $params Parameters and their values for this data object.
     */
    public static function delete_records(array $params) {
        global $DB;

        $wheresql = array();
        $dbparams = array();

        foreach ($params as $var => $value) {
            if (array_key_exists($var,  self::get_instance()->optional_fields)) {
                continue;
            }

            if (!in_array($var, self::get_instance()->required_fields)) {
                continue;
            }

            if (!is_number($value)) {
                continue;
            }

            $wheresql[] = " $var = ? ";
            $dbparams[] = $value;
        }

        if (!empty($wheresql)) {
            $wheresql = implode("AND", $wheresql);
            $DB->delete_records_select(self::get_table(), $wheresql, $dbparams);
        }
    }

}
