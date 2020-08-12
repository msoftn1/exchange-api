<?php


namespace App\Services;


use App\Currency;
use App\Exceptions\LoadDataException;
use App\Rate;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Сервис получения курсов валют.
 */
class Cbr
{
    /**
     * Получить курс валюты.
     *
     * @param string $currency
     * @param string $date
     * @return float
     * @throws LoadDataException
     */
    public function getRate(string $currency, string $date)
    {
        $rate = $this->loadRateFromDb($currency, $date);
        if ($rate === null) {
            $rate = $this->loadRateFromCbr($currency, $date);
            $this->saveRate($currency, $date, $rate);
        }

        return $rate;
    }

    /**
     * Получить список валют.
     *
     * @return array
     * @throws LoadDataException
     */
    public function getCurrencies()
    {
        $currencies = $this->loadCurrenciesFromDb();
        if (count($currencies) == 0) {
            $currencies = $this->loadCurrenciesFromCbr();
            $this->saveCurrencies($currencies);
        }

        return $currencies;
    }

    /**
     * Загрузить курс с сайта cbr.ru
     *
     * @param string $currency
     * @param string $date
     * @return float
     * @throws LoadDataException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function loadRateFromCbr(string $currency, string $date)
    {
        $client = new Client();
        $res = $client->request('GET', sprintf('http://cbr.ru/currency_base/daily/?UniDbQuery.Posted=True&UniDbQuery.To=%s', $date));

        if ($res->getStatusCode() !== 200) {
            throw new LoadDataException("Ошибка загрузки списка курсов валют.");
        }

        $rate = null;

        $crawler = new Crawler($res->getBody()->getContents());
        $crawler->filter('table.data tr')
            ->each(function (Crawler $node) use ($currency, &$rate) {

                $data = [];

                $node->filter('td')
                    ->each(function (Crawler $node) use (&$data) {
                        $data[] = $node->text();
                    });

                if (count($data) == 5 && $data[1] == $currency) {
                    $rate = $data[4];
                }
            });

        if ($rate === null) {
            throw new LoadDataException("Не удалось загрузить курс валюты.");
        }

        return (float)(str_replace(',', '.', $rate));
    }

    /**
     * Загрузить курс из базы данных.
     *
     * @param string $currency
     * @param string $date
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|null
     * @throws \Exception
     */
    private function loadRateFromDb(string $currency, string $date)
    {
        $rate = Rate::query()
            ->where('currency', $currency)
            ->where('date', (new \DateTime($date))->format('Y-m-d'))
            ->orderBy('id', 'ASC')
            ->get();

        return count($rate) > 0 ? $rate[0]->rate : null;
    }

    /**
     * Сохранить курс валюты в базу.
     *
     * @param string $currency
     * @param string $date
     * @param float $rateVal
     * @throws \Exception
     */
    private function saveRate(string $currency, string $date, float $rateVal)
    {
        $rate = new Rate();
        $rate->currency = $currency;
        $rate->date = (new \DateTime($date))->format('Y-m-d');
        $rate->rate = $rateVal;
        $rate->save();
    }

    /**
     * Сохранить список валют в базу.
     *
     * @param array $currencies
     */
    private function saveCurrencies(array $currencies)
    {
        foreach ($currencies as $currencyItem) {
            $currency = new Currency();
            $currency->currency = $currencyItem;
            $currency->save();
        }
    }

    /**
     * Загрузить список валют из базы.
     *
     * @return array
     */
    private function loadCurrenciesFromDb()
    {
        $currencies = Currency::query()
            ->orderBy('id', 'ASC')
            ->get();

        $data = [];

        foreach ($currencies as $currecny) {
            $data[] = $currecny->currency;
        }

        return $data;
    }

    /**
     * Загрузить список валют с сайта cbr.ru
     *
     * @return array
     * @throws LoadDataException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function loadCurrenciesFromCbr()
    {
        $client = new Client();
        $res = $client->request('GET', 'http://cbr.ru/currency_base/daily/');

        if ($res->getStatusCode() !== 200) {
            throw new LoadDataException("Ошибка загрузки списка курсов валют.");
        }

        $currencies = [];

        $crawler = new Crawler($res->getBody()->getContents());
        $crawler->filter('table.data tr td:nth-child(2)')
            ->each(function (Crawler $node) use (&$currencies) {
                $currencies[] = $node->text();
            });

        return $currencies;
    }
}
