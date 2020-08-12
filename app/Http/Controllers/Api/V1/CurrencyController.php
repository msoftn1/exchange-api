<?php


namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Response\JsonErrorResponse;
use App\Services\Cbr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Контроллер курсов валют.
 */
class CurrencyController extends Controller
{
    /** Сервис получения курсов валют. */
    private Cbr $cbr;

    /**
     * Коснтруктор.
     *
     * @param Cbr $cbr
     */
    public function __construct(Cbr $cbr)
    {
        $this->cbr = $cbr;
    }

    /**
     * Список валют.
     *
     * @param Request $request
     * @return Response
     */
    public function currencies(Request $request)
    {
        $page = (int)$request->get('page');
        $limit = (int)$request->get('limit');

        $dataFull = $this->cbr->getCurrencies();
        $data = $dataFull;
        if ($page != null && $limit != null) {
            $data = array_slice($data, (($page - 1) * $limit), $limit);
        }

        $response = (new Response(json_encode(['data' => $data]), 200))
            ->header('Content-Type', 'application/json');

        if ($page != null && $limit != null) {
            $response->header('X-Pagination-Total-Count', count($dataFull))
                ->header('X-Pagination-Page-Count', count($data))
                ->header('X-Pagination-Current-Page', $page)
                ->header('X-Pagination-Per-Page', $limit);
        }

        return $response;
    }

    /**
     * Курс валюту на указанную дату или на сегодня.
     *
     * @param $id
     * @param Request $request
     * @return array[]|Response
     * @throws \Exception
     */
    public function currency($id, Request $request)
    {
        $dateBefore = (new \DateTime())->modify('+1 day');

        $validator = Validator::make($request->all(), [
            'date' => sprintf('date|before:%s|after:30.06.1992', $dateBefore->format('d.m.Y'))
        ]);

        if($validator->fails()) {
            return JsonErrorResponse::fromValidator($validator);
        }

        $format = 'd.m.Y';
        $date = (new \DateTime($request->get('date', date($format))))
            ->format($format);

        return [
            'data' => [
                'rate' => $this->cbr->getRate($id, $date)
            ]
        ];
    }
}
