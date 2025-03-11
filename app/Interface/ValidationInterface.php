<?php

namespace Gab3mioni\ApiLlm\Interface;

interface ValidationInterface {
    public function validate(string $message): array;
}
