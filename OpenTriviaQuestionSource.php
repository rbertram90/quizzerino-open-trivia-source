<?php

namespace quizzerino\OpenTriviaDB;

use rbwebdesigns\quizzerino\SourceInterface;
use GuzzleHttp\Client;

/**
 * Sources questions from the open trivia DB
 * 
 * @see https://opentdb.com/api_config.php
 */
class OpenTriviaQuestionSource implements SourceInterface {

    protected $url = 'https://opentdb.com/';

    protected $client;
    protected $sessionToken = null;

    public function __construct() {
        // @todo pass client through as constructor argument
        $this->client = new Client([
            'base_uri' => $this->url
        ]);
    }

    /**
     * Request a session token - these last for 6 hours
     */
    protected function getSessionToken() {
        if (is_null($this->sessionToken)) {
            $response = $this->client->request('GET', 'api_token.php', [
                'query' => [
                    'command' => 'request'
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $json = $response->getBody()->getContents();
                $data = json_decode($json);
                $this->sessionToken = $data['token'];
            }
            else {
                print "ERROR: Unable to get session token" . PHP_EOL;
            }
        }

        return $this->sessionToken;
    }

    public function getQuestion(): array {
        $token = $this->getSessionToken();

        $response = $this->client->request('GET', 'api.php', [
            'query' => [
                'amount' => 1,
                'token' => $token
            ],
        ]);

        if ($response->getStatusCode() == 200) {
            $json = $response->getBody()->getContents();
            $data = json_decode($json);

            $result = $data['results'][0];
            // Randomly place the correct answer
            $correct_index = rand(0, count($result['incorrect_answers']) + 1);

            // Add the correct answer in the random index into the other incorrect answers list
            array_splice($result['incorrect_answers'], $correct_index, 0, $result['correct_answer']);
            
            return [
                'text' => $result['question'],
                'options' => $result['incorrect_answers'],
                'correct_option_index' => $correct_index,
            ];
        }
        else {
            print "ERROR: Unable to get question data" . PHP_EOL;

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