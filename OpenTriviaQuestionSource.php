<?php

namespace quizzerino\OpenTriviaDB;

use rbwebdesigns\quizzerino\SourceInterface;

class OpenTriviaQuestionSource implements SourceInterface {

    public function getQuestion(): array {
        // Example question for testing!
        return [
            'text' => 'How many roads must a man walk down, before you can call him a man?',
            'options' => [
                '50',
                '200',
                '5000',
                'The answer my friend is blowing in the wind'
            ],
            'correct_option_index' => 3,
        ];
    }

}