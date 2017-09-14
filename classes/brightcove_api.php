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
 * Brightcove API interface for mod_brightcove activities.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_brightcove;

use coding_exception;
use context_module;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Brightcove API interface for mod_brightcove activities.
 *
 * @package     mod_brightcove
 * @copyright   2017 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class brightcove_api {

    /**
     * Brightcove activity instance.
     * @var \stdClass
     */
    protected $instance;

    /**
     * Brightcove video ID.
     * @var string
     */
    protected $videoid;

    /**
     * Activity context.
     * @var \context_module
     */
    protected $context;

    /**
     * Initialises the class.
     * Makes relevant configuration from config available and
     * creates Guzzle client.
     *
     * @param \stdClass $moduleinstance Activity instance.
     */
    public function __construct($handler = false) {
        $this->config = get_config('brightcove');
        $this->accountid = $this->config->accountid;
        $this->playerid = $this->config->playerid;
        $this->apikey = $this->config->apikey;
        $this->apisecret = $this->config->apisecret;
        $this->oauthendpoint = $this->config->oauthendpoint;
        $this->apiendpoint = $this->config->apiendpoint;
        $this->limit = $this->config->perpage;
        $this->context = '';

        // Allow the caller to instansite the Guzzle client
        // with a custom handler.
        if ($handler) {
            $this->client = new \GuzzleHttp\Client(['handler' => $handler]);
        } else {
            $this->client = new \GuzzleHttp\Client();
        }

    }

    public function set_context($context) {
        $this->context = $context;
    }

    /**
     * Generates OAuth token from stored key and secret deatils.
     * Token is used to make API calls.
     *
     * @return string $token the API acesss token.
     */
    private function generate_token() {
        $url = $this->config->oauthendpoint. 'access_token';
        $authcreds = base64_encode ($this->apikey.':'.$this->apisecret);
        $authstring = 'Basic '.$authcreds;
        $headers = ['Authorization' => $authstring];
        $params = ['headers' => $headers,
                'form_params' => ['grant_type' => 'client_credentials']
        ];

        $response = $this->client->request('POST', $url, $params);
        $responseobj = json_decode($response->getBody(), true);
        $accesstoken = $responseobj['access_token'];

        // Update cache with new token.
        $cache = \cache::make('mod_brightcove', 'apitoken');
        $cache->set('token', $accesstoken);

        return $accesstoken;

    }

    /**
     * Convert video duration in milliseconds to string
     * of minutes and seconds
     *
     * @param string $duration video duration in milliseconds
     */
    private function video_duration($duration) {
        $rawseconds = $duration / 1000;
        $minutes = intval($rawseconds/ 60);
        $seconds = $rawseconds % 60;

        $durationstring = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);;

        return $durationstring;
    }

    /**
     * Get the current API token.
     * Tries to get token from cache first, if cache isn't set
     * token is generated.
     *
     * @return string|mixed
     */
    private function get_token() {
        $cache = \cache::make('mod_brightcove', 'apitoken');
        $token = '';

        if (!$cache->get('token')) {
            // Token doesn't exist, get one.
            $token = $this->generate_token();
        } else {
            $token = $cache->get('token');
        }

        return $token;
    }

    /**
     * Calls the brightcove CMS API.
     *
     * @param string $url Brightcove service endpoint to call
     * @param bool $retry
     * @return object $responseobj The response recevied from the API
     */
    public function call_api($url, $retry=true) {
        $token = $this->get_token();
        $params = ['headers' => ['Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token]];

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // response.
        try {
            $response = $this->client->request('GET', $url, $params);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        $responsecode = $response->getStatusCode();
        $responseobj = json_decode($response->getBody(), true);

        // If we get a 401 response code it is likely our Bearer token has expired.
        // In this case we generate a new token and retry getting video details.
        // We only retry once.
        if ($responsecode == 401 && $retry == true) {
            $this->generate_token();
            $responseobj = $this->call_api($url, false);
        }

        return $responseobj;
    }

    /**
     * Get the number of pages of videos for a given Brightcove account.
     *
     * @param string $q the search query to match
     * @return int $pages Number of pages.
     */
    public function get_video_pages($q) {
        // Handle searching
        if ($q != '*') {
            $query = '?q=' . urlencode($q);
        } else {
            $query = '';
        }

        $url = $this->apiendpoint. 'accounts/' . $this->accountid . '/counts/videos' . $query;
        $count = $this->call_api($url);
        $pages = ceil($count['count'] / $this->limit);

        return $pages;
    }

    /**
     * Get video list details from Brightcove API.
     *
     * @param int $page the result page to return
     * @param string $q the search query to match
     * @return array $results array of video info objects.
     */
    public function get_video_list($q, $page) {
        $pages = $this->get_video_pages($q);
        $results = array();
        $results['videos'] = array();
        $results['pages'] = array();
        $thumbnailurl = '';

        // Handle paging and offset.
        if ($page == 0) {
            $page = $pages;
        }
        $offset = ($page - 1) * $this->limit;

        // Handle searching
        if ($q != '*') {
            $query = '&q=' . urlencode($q);
        } else {
            $query = '';
        }

        $url = $this->apiendpoint. 'accounts/' . $this->accountid . '/videos?limit='. $this->limit . '&offset=' . $offset . $query;
        $videos = $this->call_api($url);

        // Format response
        foreach ($videos as $video){
            if (isset($video['images']['thumbnail']['src'])) {
                $thumbnailurl= $video['images']['thumbnail']['src'];
            }

            if ($video['complete']){
                $complete = 'check';
            }
            else {
                $complete = 'warning';
            }

            $record = new \stdClass();
            $record->id = $video['id'];
            $record->name = $video['name'];
            $record->complete = $complete;
            $record->created_at = date('d/m/Y h:i:s A', strtotime($video['created_at']));
            $record->duration = $this->video_duration($video['duration']);
            $record->thumbnail_url = $thumbnailurl;

            $results['videos'][] = $record;
        }

        for ($i = 1; $i <= $pages; $i++) {
            $results['pages'][] = array('page' => $i);
        }

        return $results;
    }

    public function get_videoplayer_js_url() {
        $videoplayerurl = '//players.brightcove.net/';
        $videoplayerurl .= $this->accountid;
        $videoplayerurl .= '/';
        $videoplayerurl .= $this->playerid;
        $videoplayerurl .= '_default/index';

        return $videoplayerurl;
    }

    /**
     * Given a Video ID return Brightcove video object.
     *
     * @param int $id The Brightcove ID of the video.
     * @return \stdClass $record The video record details.
     */
    public function get_video_by_id($id) {
        $url = $this->config->apiendpoint. 'accounts/' . $this->accountid . '/videos/' . $id;
        $video = $this->call_api($url);

        if (isset($video['images']['thumbnail']['src'])) {
            $thumbnailurl= $video['images']['thumbnail']['src'];
        }

        if ($video['complete']){
            $complete = 'check';
        }
        else {
            $complete = 'warning';
        }

        $record = new \stdClass();
        $record->id = $video['id'];
        $record->name = $video['name'];
        $record->complete = $complete;
        $record->created_at = date('d/m/Y h:i:s A', strtotime($video['created_at']));
        $record->duration = $this->video_duration($video['duration']);
        $record->thumbnail_url = $thumbnailurl;
        $record->text_tracks = $video['text_tracks'];

        return $record;
    }

    /**
     * Gets the transcript file from Brightcove via the API
     * and save it as a local file in Moodle.
     *
     * @return void
     */
    public function save_transcript($videoid) {
        $texttrack = $this->get_transcript_url($videoid, false);
        $fs = get_file_storage();
        $file = $this->get_transcript_file($videoid);

        // Delete existing file.
        if ($file) {
            $file->delete();
        }

        // Create a new file from external track URL if it exists.
        if ($texttrack != '') {
            $fs->create_file_from_url($this->build_transcript_file_record($videoid), $texttrack);
        }
    }

    /**
     * Delete local transcript file.
     */
    public function delete_transcript() {
        $file = $this->get_transcript_file();

        if ($file) {
            $file->delete();
        }
    }

    /**
     * Return transcript file.
     *
     * @return bool|\stored_file False if not found.
     */
    public function get_transcript_file($videoid) {
        $fs = get_file_storage();
        $filerecord = $this->build_transcript_file_record($videoid);

        return $fs->get_file(
                $filerecord['contextid'],
                $filerecord['component'],
                $filerecord['filearea'],
                $filerecord['itemid'],
                $filerecord['filepath'],
                $filerecord['filename']
                );
    }

    /**
     * Build transcript file record.
     *
     * @return array
     */
    public function build_transcript_file_record($videoid) {
        return array(
                'contextid' => $this->context->id,
                'component' => 'mod_brightcove',
                'filearea' => 'transcript',
                'itemid' => $videoid,
                'filepath' => '/',
                'filename' => 'transcript.vtt'
        );
    }

    /**
     * Returns text track details for the given video ID.
     * Only details for the first track are returned.
     *
     * @param bool $internal True if we want an internal transcript URL.
     * @return string $texttrack URL of first found track location.
     */
    public function get_transcript_url($videoid, $internal = true) {
        $texttrack = '';

        if ($internal) {
            $texttrack = $this->make_transcript_file_url($videoid, false);
        } else {
            $videoobj = $this->get_video_by_id($videoid);
            $texttracks = $videoobj->text_tracks;

            if (array_key_exists(0, $texttracks)) {
                $texttrack = $texttracks[0]['src'];
            }
        }

        return $texttrack;
    }

    /**
     * Returns transcript download URL for the given video ID.
     *
     * @return string URL of first found track location.
     */
    public function get_transcript_download_url() {
        $downloadurl = new moodle_url('/mod/brightcove/export.php', array('id' => $this->context->instanceid, 'type' => 1));

        return $downloadurl->out(false);
    }

    /**
     * Male transctipt file URL.
     *
     * @param bool $forcedownload
     *
     * @return string
     */
    public function make_transcript_file_url($videoid, $forcedownload = false) {
        $fileurl = '';
        $file = $this->get_transcript_file($videoid);
        if ($file) {
            $fileurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    $forcedownload
                    )->out();
        }

        return $fileurl;
    }

    /**
     * Formats a given transcript ready for user download.
     * Transcript timming information etc is stripped out.
     *
     * @param string $transcript The transcript content to clean.
     * @return string $cleantranscript Cleaned transcript content.
     */
    public function transcript_format_for_download($transcript) {
        $transcript = preg_replace('/WEBVTT[\n\r]+/', '', $transcript);
        $transcript = preg_replace('/[0-9]+\:[0-9]{2}\:[0-9]{2}\.[0-9]+\s\-\-\>\s[0-9]+\:[0-9]{2}\:[0-9]{2}\.[0-9]+/', '', $transcript);
        $transcript = preg_replace('/[\n\r]+/', "\n", $transcript);
        $transcript = preg_replace('/\,[\n\r]/', ", ", $transcript);
        $transcript = strip_tags($transcript);

        return $transcript;
    }

    /**
     * Gets transcript content for a given video
     * Only details for the first track are returned.
     *
     * @param bool $format Should the returned copntent be formatted for download or raw.
     * @param bool $internal True if we want an internal transcript URL.
     *
     * @return string $trackcontent Content of the first found text track.
     */
    public function get_transcript_content($format = true, $internal = true) {
        if ($internal) {
            $content = $this->get_transcript_file()->get_content();
        } else {
            $trackurl = $this->get_transcript_url($internal);
            $response = $this->client->request('GET', $trackurl);
            $content = $response->getBody();
        }

        if ($format) {
            $trackcontent = $this->transcript_format_for_download($content);
        } else {
            $trackcontent = $content;
        }

        return $trackcontent;
    }
}