<?
namespace Ptb\Utils;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\Json;
use Bitrix\Main\SystemException;

/**
 * Получение ближайшей станции метро по адресу или координатам
 * 
 * @author Nikolai Revin
 *
 */
class Metro
{

    const API_URL = 'https://geocode-maps.yandex.ru/1.x/';

    protected $adress;

    protected $coords;

    protected $client = null;

    public function __construct(string $adress, string $coords = '')
    {
        $adress = trim($adress);
        $coords = trim($coords);

        if (empty($adress) || $coords) {
            throw new SystemException('Adress or Coords is empty!');
        }

        $this->adress = $adress;
        $this->initHttpClient();
    }

    protected function initHttpClient()
    {
        if (is_null($this->client)) {
            $this->client = new HttpClient();
            $this->client->setHeader('Content-Type', 'application/json', true);
        }
    }

    protected function getCoordsUrl(): string
    {
        $oUrl = new Uri(Metro::API_URL);
        $oUrl->addParams([
            'format' => 'json',
            'geocode' => $this->adress,
            'result' => 1
        ]);
        return $oUrl->getUri();
    }

    protected function getMetroUrl(): string
    {
        $oUrl = new Uri(Metro::API_URL);
        $oUrl->addParams([
            'format' => 'json',
            'geocode' => $this->coords,
            'result' => 1,
            'kind' => 'metro'
        ]);
        return $oUrl->getUri();
    }

    protected function getData(array $response, $field = 'coords')
    {
        $result = array_shift($response['response']['GeoObjectCollection']['featureMember']);
        $result = $result['GeoObject'];
        $value = '';

        switch ($field) {
            case 'coords':
                $value = $result['Point']['pos'] ?: '';
                break;
            case 'metro':
                $value = $result['name'] ?: '';
                break;
        }

        return $value;
    }

    protected function getCoords()
    {
        $response = $this->client->get($this->getCoordsUrl());
        $response = Json::decode($response);
        return $this->getData($response);
    }

    protected function getMetro()
    {
        $response = $this->client->get($this->getMetroUrl());
        $response = Json::decode($response);
        return $this->getData($response, 'metro');
    }

    public function getStationName(): string
    {
        if (! $this->coords) {
            $this->coords = $this->getCoords();
        }

        return $this->getMetro();
    }
}
