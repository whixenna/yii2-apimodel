<?php
namespace app\models;

use Yii;
use whixenna\apimodel\components\httpclient\ApiRequest;
use whixenna\apimodel\components\httpclient\ApiResponse;
use whixenna\apimodel\models\ApiModel;
use whixenna\apimodel\interfaces\SelectListInterface;
use whixenna\apimodel\traits\SelectListTrait;

/**
 * пример модели 1го уровня, доступной для получения в API
 *
 * @property string $label
 * @property string $ean
 * @property string $name
 *
 * SelectListTrait
 * public @static getList()
 * public @static getSelect2List()
 */
class ExampleApiModel extends ApiModel implements SelectListInterface {
    use SelectListTrait;

    public $id;
    public $name;

    const FULL_NAME_TEMPLATE = '{name} [{id}]';

    const RESULT_PARAM_TOTAL = 'total';
    const RESULT_PARAM_RECORD = 'example';
    const RESULT_PARAM_RECORDS = 'examples';

    public function rules() {
        return [
            [['id'], 'required', 'on' => self::SCENARIO_UPDATE],
            [['id', 'name'], 'string'],
        ];
    }

    public function attributeLabels() {
        return [
            'id'    => 'ID',
            'name'  => 'Name',
        ];
    }

    /** @return string */
    public static function primaryKey() {
        return 'id';
    }

    /**
     * JSON массив со всеми записями
     * @param array $query
     * @param string $dataParam
     * @return array|bool
     */
    public static function getAllRecords (array $query = [], $dataParam = self::RESULT_PARAM_RECORDS) {
        /**
         * /examples/all
         * @var $request ApiRequest
         * @var $response ApiResponse - например {"status": "ok", "examples": [{}]}
         */
        $client = self::getHttpClient();
        $request = $client->createApiRequest(array_merge([
            'examples/all',
        ], $query), 'get');
        $response = $request->send();
        return $dataParam ? $response->getData($dataParam) : $response->getData();
    }

    /**
     * JSON с одной записью
     * @param $id
     * @param string $dataParam
     * @return array|bool
     */
    public static function getRecord ($id, $dataParam = self::RESULT_PARAM_RECORD) {
        /**
         * /examples/find?id=<id, обязательный>
         * @var $request ApiRequest
         * @var $response ApiResponse - например {"status": "ok", "example": {}}
         */
        $client = self::getHttpClient();
        $request = $client->createApiRequest([
            'examples/find',
            'id' => $id,
        ], 'get');
        $response = $request->send();
        return $dataParam ? $response->getData($dataParam) : $response->getData();
    }

    /**
     * сохранить (создать или обновить)
     * @return boolean
     */
    public function save() {
        if (!$this->validate())
            return false;

        /**
         * /examples/update
         * @var $request ApiRequest
         * @var $response ApiResponse
         */
        $client = $this->getClient();
        $attributes = $this->getFilledAttributes();
        $request = $client->createApiRequest('examples/update', 'post', $attributes);
        $response = $request->send();
        return $response->isOk;
    }

    /**
     * удалить
     * @return boolean
     */
    public function delete() {
        /**
         * /examples/delete
         * @var $request ApiRequest
         * @var $response ApiResponse
         */
        $client = $this->getClient();
        $attributes = $this->getFilledAttributes();
        $request = $client->createApiRequest(["examples/delete", 'id' => $this->id], 'get');
        $response = $request->send();
        return $response->isOk;
    }
}