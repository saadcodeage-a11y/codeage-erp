<?php

namespace App\Services;

use InvalidArgumentException;

class MathExpressionEvaluator
{
    protected array $tokens = [];
    protected int $position = 0;
    protected array $variables = [];

    public function evaluate(string $expression, array $variables = []): float
    {
        $expression = trim($expression);

        if ($expression === '') {
            throw new InvalidArgumentException('Formula cannot be empty.');
        }

        $this->tokens = $this->tokenize($expression);
        $this->position = 0;
        $this->variables = $variables;

        $result = $this->parseExpression();

        if ($this->currentToken() !== null) {
            throw new InvalidArgumentException('Unexpected token in formula.');
        }

        return round($result, 6);
    }

    protected function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $offset = 0;

        while ($offset < $length) {
            $char = $expression[$offset];

            if (ctype_space($char)) {
                $offset++;
                continue;
            }

            if (preg_match('/\G\d+(?:\.\d+)?/A', $expression, $matches, 0, $offset)) {
                $tokens[] = ['type' => 'number', 'value' => (float) $matches[0]];
                $offset += strlen($matches[0]);
                continue;
            }

            if (preg_match('/\G[a-zA-Z_][a-zA-Z0-9_]*/A', $expression, $matches, 0, $offset)) {
                $tokens[] = ['type' => 'identifier', 'value' => $matches[0]];
                $offset += strlen($matches[0]);
                continue;
            }

            if (str_contains('+-*/%^()', $char)) {
                $tokens[] = ['type' => 'operator', 'value' => $char];
                $offset++;
                continue;
            }

            throw new InvalidArgumentException('Unsupported character in formula.');
        }

        return $tokens;
    }

    protected function parseExpression(): float
    {
        $value = $this->parseTerm();

        while (($token = $this->currentToken()) && in_array($token['value'], ['+', '-'], true)) {
            $operator = $token['value'];
            $this->position++;
            $right = $this->parseTerm();
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    protected function parseTerm(): float
    {
        $value = $this->parsePower();

        while (($token = $this->currentToken()) && in_array($token['value'], ['*', '/', '%'], true)) {
            $operator = $token['value'];
            $this->position++;
            $right = $this->parsePower();

            if (in_array($operator, ['/', '%'], true) && abs($right) < 0.0000001) {
                throw new InvalidArgumentException('Division by zero is not allowed in formulas.');
            }

            $value = match ($operator) {
                '*' => $value * $right,
                '/' => $value / $right,
                '%' => fmod($value, $right),
            };
        }

        return $value;
    }

    protected function parsePower(): float
    {
        $value = $this->parseUnary();

        if (($token = $this->currentToken()) && $token['value'] === '^') {
            $this->position++;
            $right = $this->parsePower();
            $value = $value ** $right;
        }

        return $value;
    }

    protected function parseUnary(): float
    {
        $token = $this->currentToken();

        if ($token && in_array($token['value'], ['+', '-'], true)) {
            $this->position++;
            $value = $this->parseUnary();

            return $token['value'] === '-' ? -$value : $value;
        }

        return $this->parsePrimary();
    }

    protected function parsePrimary(): float
    {
        $token = $this->currentToken();

        if ($token === null) {
            throw new InvalidArgumentException('Incomplete formula.');
        }

        if ($token['type'] === 'number') {
            $this->position++;

            return (float) $token['value'];
        }

        if ($token['type'] === 'identifier') {
            $this->position++;

            if (! array_key_exists($token['value'], $this->variables)) {
                throw new InvalidArgumentException("Unknown variable [{$token['value']}] in formula.");
            }

            return (float) $this->variables[$token['value']];
        }

        if ($token['value'] === '(') {
            $this->position++;
            $value = $this->parseExpression();

            if (($closing = $this->currentToken()) === null || $closing['value'] !== ')') {
                throw new InvalidArgumentException('Missing closing parenthesis in formula.');
            }

            $this->position++;

            return $value;
        }

        throw new InvalidArgumentException('Invalid formula syntax.');
    }

    protected function currentToken(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }
}
