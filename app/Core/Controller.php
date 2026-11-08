<?php

namespace App\Core;

/**
 * Base Controller Class
 * Receives requests, validates inputs, calls models, and sends responses.
 * Never contains direct SQL queries.
 */
abstract class Controller
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * Controller constructor
     *
     * @param Request|null $request
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? new Request();
    }

    /**
     * Return a standardized JSON success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        Response::success($data, $message, $statusCode);
    }

    /**
     * Return a standardized JSON error response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $data
     * @return void
     */
    protected function error(string $message = 'An error occurred', int $statusCode = 400, mixed $data = null): void
    {
        Response::error($message, $statusCode, $data);
    }

    /**
     * Validate an input array against defined rules
     *
     * Supported rules:
     * - required
     * - email
     * - min:length
     * - max:length
     * - numeric
     * - integer
     *
     * @param array $data Input data
     * @param array $rules Validation rules, e.g. ['email' => 'required|email|max:150']
     * @return array Array of validation error messages (empty if valid)
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;

                    case 'min':
                        if (!empty($value) && strlen((string)$value) < (int)$param) {
                            $errors[$field][] = "The {$field} must be at least {$param} characters.";
                        }
                        break;

                    case 'max':
                        if (!empty($value) && strlen((string)$value) > (int)$param) {
                            $errors[$field][] = "The {$field} may not be greater than {$param} characters.";
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "The {$field} must be a number.";
                        }
                        break;

                    case 'integer':
                        if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field][] = "The {$field} must be an integer.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
