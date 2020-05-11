<?php

namespace quizzerino\OpenTriviaDB;

use rbwebdesigns\quizzerino\SourceInterface;
use rbwebdesigns\quizzerino\Logger;
use GuzzleHttp\Client;

/**
 * Sources questions from the Open Trivia DB
 * 
 * @see https://opentdb.com/api_config.php
 */
class OpenTriviaQuestionSource implements SourceInterface {

    /** @var string Endpoint URL */
    protected $url = 'https://opentdb.com/';

    /** @var \GuzzleHttp\Client HTTP Client */
    protected $client;

    /** @var string Session token to pass to API so that questions are not duplicated */
    protected $sessionToken = null;

    /** @var mixed[] Cache of questions to prevent calling API everytime */
    protected $questionStore = [];

    /** @var int ID of category to use */
    protected $quizCategory;

    /**
     * OpenTriviaQuestionSource constructor
     */
    public function __construct($settings) {
        // @todo pass client through as constructor argument
        $this->client = new Client([
            'base_uri' => $this->url
        ]);
        $this->quizCategory = intval($settings->category);
    }

    /**
     * Request a session token - these last for 6 hours
     */
    protected function getSessionToken() : string {
        if (is_null($this->sessionToken)) {
            $response = $this->client->request('GET', 'api_token.php', [
                'query' => [
                    'command' => 'request'
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $json = $response->getBody()->getContents();
                $data = json_decode($json);
                $this->sessionToken = $data->token;
            }
            else {
                Logger::error("Unable to get session token");
            }
        }

        return $this->sessionToken;
    }

    /**
     * Get a question from API or cache
     */
    public function getQuestion() : array {

        // Have we still got questions left to ask from a
        // previous API call?
        if (count($this->questionStore) > 0) {
            $result = array_shift($this->questionStore);

            // Randomly place the correct answer
            $correct_index = rand(0, count($result['incorrect_answers']));

            // Add the correct answer in the random index into the other incorrect answers list
            array_splice($result['incorrect_answers'], $correct_index, 0, $result['correct_answer']);

            return [
                'text' => $result['question'],
                'options' => $result['incorrect_answers'],
                'correct_option_index' => $correct_index,
            ];
        }

        $token = $this->getSessionToken();

        $response = $this->client->request('GET', 'api.php', [
            'query' => [
                'amount' => 10,
                'category' => $this->quizCategory,
                'token' => $token
            ],
        ]);

        if ($response->getStatusCode() == 200) {
            $json = $response->getBody()->getContents();
            $data = json_decode($json, true);

            $this->questionStore = $data['results'];
            $result = array_shift($this->questionStore);

            // Randomly place the correct answer
            $correct_index = rand(0, count($result['incorrect_answers']));

            // Add the correct answer in the random index into the other incorrect answers list
            array_splice($result['incorrect_answers'], $correct_index, 0, $result['correct_answer']);
            
            return [
                'text' => $result['question'],
                'options' => $result['incorrect_answers'],
                'correct_option_index' => $correct_index,
            ];
        }
        else {
            Logger::error("Unable to get question data");

            // Return a hard coded question so things don't break
            // @todo handle better!
            return [
                'text' => 'How many roads must a man walk down, before you can call him a man?',
                'options' => ['50','200','5000','The answer my friend is blowing in the wind'],
                'correct_option_index' => 3,
            ];
        }
    }

}