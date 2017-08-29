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
        $this->apikey = $this->config->apikey;
        $this->apisecret = $this->config->apisecret;
        $this->oauthendpoint = $this->config->oauthendpoint;
        $this->apiendpoint = $this->config->apiendpoint;
        $this->limit = $this->config->perpage;

        // Allow the caller to instansite the Guzzle client
        // with a custom handler.
        if ($handler) {
            $this->client = new \GuzzleHttp\Client(['handler' => $handler]);
        } else {
            $this->client = new \GuzzleHttp\Client();
        }

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
     * @return int $pages Number of pages.
     */
    public function get_video_pages() {
        $url = $this->config->apiendpoint. 'accounts/' . $this->accountid . '/counts/videos';
        $count = $this->call_api($url);
        $pages = ceil($count['count'] / $this->limit);

        return $pages;
    }

    /**
     * Get video list details from Brightcove API.
     *
     * @return array $results array of video info objects.
     */
    public function get_video_list($page) {
        $pages = $this->get_video_pages();
        $results = array();
        $results['videos'] = array();
        $results['pages'] = array();
        $thumbnailurl = '';

        // Handle paging and offset.
        if ($page == 0) {
            $page = $pages;
        }
        $offset = ($page - 1) * $this->limit;

        $url = $this->config->apiendpoint. 'accounts/' . $this->accountid . '/videos?limit='. $this->limit . '&offset=' . $offset;
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

        return $record;
    }
}