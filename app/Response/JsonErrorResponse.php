<?php


namespace App\Response;


use Illuminate\Http\Response;

/**
 * Класс генерирующий Response объект для ошибочных запросов.
 */
class JsonErrorResponse
{
    /**
     * Генерирует Response объект по валидатору.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return Response
     */
    public static function fromValidator(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $data = [
            'data' => [
                'errors' => $validator->errors()->messages()
            ]
        ];

        return (new Response(json_encode($data), 400))
            ->header('Content-Type', 'application/json');
    }
}
