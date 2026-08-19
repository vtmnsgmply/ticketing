<?php

namespace App\Exceptions;

class ModerationException extends BusinessRuleException
{
    public function __construct(
        string $message = 'Your message contains language that is not permitted. Please revise it and try again.',
        private readonly array $errors = ['message' => ['Prohibited language detected.']],
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
