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
     * Initialises the class.
     * Makes relevant configuration from config available and
     * creates Guzzle client.
     *
     * @return void
     */
    public function __construct() {
        $this->config = get_config('brightcove');
        $this->accountid = $this->config->accountid;
        $this->apikey = $this->config->apikey;
        $this->apisecret = $this->config->apisecret;
        $this->oauthendpoint = $this->config->oauthendpoint;
        $this->apiendpoint = $this->config->apiendpoint;

        $this->client = new \GuzzleHttp\Client();

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
     * Get video object from Brightcove API.
     * Returns data about video based on given video ID.
     *
     * @param string $videoid The Brightcove ID of the video.
     * @param bool $retry Set true if we are retying based on auth denied condition.
     */
    public function get_video($videoid, $retry=true) {
        $url = $this->config->apiendpoint. 'accounts/' . $this->accountid . '/videos/' . $videoid;
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
            $responseobj = $this->get_video($videoid, false);
        }

        return $responseobj;
    }

    /**
     * Returns text track details for the given video ID.
     * Only details for the first track are returned.
     *
     * @param string $videoid The Brightcove ID of the video.
     * @return string $texttrack URL of first found track location.
     */
    public function get_transcript($videoid) {
        $videoobj = $this->get_video($videoid);
        $texttracks = $videoobj['text_tracks'];
        $texttrack= '';

        if (array_key_exists(0, $texttracks)) {
            $texttrack = $texttracks[0]['src'];
        }

        return $texttrack;

    }

    /**
     * Formats a given transcript ready for user download.
     * Transcript timming information etc is stripped out.
     *
     * @param string $transcript The transcript content to clean.
     * @return string $cleantranscript Cleaned transcript content.
     */
    public function transcript_format_for_download($transcript) {

        return $transcript;
    }

    /**
     * Gets transcript content for a given video
     * Only details for the first track are returned.
     *
     * @param string $videoid The Brightcove ID of the video.
     * @param bool $format Should the returned copntent be formatted for download or raw.
     * @return string $trackcontent Content of the first found text track.
     */
    public function get_transcript_content($videoid, $format=false) {
        $trackurl = $this->get_transcript($videoid);
        $trackcontent = '';

        $response = $this->client->request('GET', $trackurl);

        if ($format){
            $trackcontent = $this->transcript_format_for_download($response->getBody());
        } else {
            $trackcontent = $response->getBody();
        }


        return $trackcontent;
    }

    /**
     * Returns video name for the given video ID.
     *
     * @param string $videoid The Brightcove ID of the video.
     * @return string $videoname Video name.
     */
    public function get_videoname($videoid) {
        $videoobj = $this->get_video($videoid);
        $videoname = $videoobj['name'];

        return $videoname;
    }
}