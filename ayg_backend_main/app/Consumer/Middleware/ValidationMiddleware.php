<?php

namespace App\Consumer\Middleware;

use App\Consumer\Errors\ValidationError;
use App\Consumer\Responses\Response;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;

class ValidationMiddleware
{
    public function __construct()
    {
        v::with('App\\Consumer\\Validation\\Rules', true);
    }

    /**
     * @param array $data
     * @param \Respect\Validation\Validator[] $rules
     * @param $errorPrefix
     * @return array
     */
    protected static function validateByDataAndRules($data, $rules, $errorPrefix)
    {
        $validationErrorList = [];
        foreach ($data as $key => $value) {
            try {
                $rules[$key]->check($value);
            } catch (ValidationException $e) {
                $validationErrorList[] = $e->getMainMessage();
            }
        }

        if (!empty($validationErrorList)) {
            (new Response(null, null, new ValidationError($validationErrorList)))->returnJson();
        }
    }
}