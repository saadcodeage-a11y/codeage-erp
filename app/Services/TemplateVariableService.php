<?php

namespace App\Services;

/**
 * Service responsible for providing system variables that can be used in email templates.
 * It also contains helper methods for replacing those variables with actual values.
 */
class TemplateVariableService
{
    /**
     * Return an associative array of available variables and a short description.
     *
     * Example:
     * [
     *   'employeeName' => 'Full name of the employee',
     *   'formLink' => 'URL to the related form',
     * ]
     */
    public static function getAvailableVariables(): array
    {
        return [
            'employeeName' => 'Full name of the employee',
            'formLink' => 'URL to the related form',
        ];
    }

    /**
     * Replace placeholders in a given template string with actual values.
     * This is a simple implementation used for preview purposes.
     *
     * @param string $content The template content containing placeholders like {{employeeName}}
     * @param array  $data    Key/value pairs where the key matches a variable name.
     * @return string The content with placeholders replaced.
     */
    public static function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = "{{{$key}}}";
            $content = str_replace($placeholder, $value, $content);
        }
        return $content;
    }
}
