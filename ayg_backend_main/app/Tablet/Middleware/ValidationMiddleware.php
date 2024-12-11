<?php

namespace App\Tablet\Middleware;

use App\Tablet\Errors\ValidationError;
use App\Tablet\Responses\Response;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;

class ValidationMiddleware
{
    public function __construct()
    {
        v::with('App\\Tablet\\Validation\\Rules', true);
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
            $validationError=new ValidationError($errorPrefix, [$validationErrorList], implode (', ',$validationErrorList));
            json_error(ValidationError::CODE, ValidationError::MESSAGE, implode (', ',$validationErrorList), '', 1);
            (new Response(null, null, $validationError))->returnJson();
        }
    }
}
