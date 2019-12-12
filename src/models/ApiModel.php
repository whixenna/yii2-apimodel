<?php
namespace whixenna\apimodel\models;

use Yii;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use whixenna\apimodel\interfaces\ApiModelInterface;
use whixenna\apimodel\traits\AttributesTrait;
use whixenna\apimodel\traits\NestingTrait;
use whixenna\apimodel\traits\JsonAttributesTrait;
use whixenna\apimodel\components\httpclient\ApiClient;

/**
 * Class ApiModel
 *
 * при создании наследника следует создать методы для получения JSON атрибутов соответствующих моделей:
 * protected static @method ::getAllRecords     - метод должен возвращать массив массивов атрибутов моделей или false
 * protected static @method ::getRecord         - метод должен возвращать массив массивов атрибутов моделей или false
 * public    static @method ::primaryKey        - метод должен возвращать имя свойства/члена массива, используемого как первичный ключ
 *
 * при необходимости задать наследнику свойства:
 * @method ->getRelations() - метод должен возвращать массив ['attribute' => 'namespace\Class'] связанных моделей,
 * которые будут созданы из массива, загруженного в поле attribute, с использованием указанного класса,
 * единичная модель для ассоциативного массива и массив моделей для числового, где подмассивы
 * будут восприняты как единичные модели.
 * если вместо имени класса указать 'attribute' => false, связанные модели не будут созданы.
 *
 * при необходимости реализовать методы:
 * public @method ->save
 * public @method ->delete
 *
 * AttributesTrait
 * @method getBooleanAttributes()
 * @method getSafeAttributes()
 * @method string getDecoded($attribute)
 * @method array filterFilledAttributes()
 *
 * NestingTrait
 * private @property $_parent
 * private @property $_related
 * public static @method createModel (array $attributes, $scenario = null) @return static
 * public static @method primaryKey @return mixed
 * public @method setNestedAttributes (array $values)
 * public @method getNestedAttributes (string $name)
 * public @method setParent($this)
 * public @method getParent()
 * public @method getRelations()
 * public @method setRelation ($name, $value, $asArray = false)
 * public @method getRelationModel($name, $id, $cached = true)()
 * public @method getRelationModels($name, array $query = [], $cached = true)
 */
abstract class ApiModel extends Model implements ApiModelInterface {
    use AttributesTrait,
        NestingTrait,
        JsonAttributesTrait {
            JsonAttributesTrait::getBooleanAttributes insteadof AttributesTrait;
        }

    protected $_oldAttributes;
    protected $_httpClient;

    const HTTP_CLIENT_COMPONENT = 'apiHttpClient';
    const EVENT_AFTER_FIND = 'afterFind';
    const EVENT_AFTER_INIT = 'afterInit';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_CREATE = 'create';

    //получить значения или связанные модели
    public function __get ($name) {
        return $this->getNestedAttributes($name);
    }

    //преобразовать JSON в значения и связанные модели
    public function setAttributes ($values, $safeOnly = false) {
        $this->_oldAttributes = array_merge($this->attributes, $this->_related);
        $values = $this->setNestedAttributes($values);

        //преобразовать обычные атрибуты под нужный формат
        $values = $this->normalizeAttributes($values);
        parent::setAttributes($values, $safeOnly);

        $this->trigger(static::EVENT_AFTER_INIT);
    }

    /** @return array|null */
    public function getOldAttributes() {
        return $this->_oldAttributes;
    }


    /**
     * получить модели из API
     * @param array $query
     * @return static[]
     */
    public static function findAll ($query = []) {
        $models = [];
        if ($data = static::getAllRecords($query)) {
            foreach ($data as $item) {
                if (($model = static::createModel($item)) && ($model instanceof static)) {
                    $models[] = $model;
                    $model->trigger(static::EVENT_AFTER_FIND, Yii::createObject([
                        'class' => ModelEvent::class,
                        'data' => [
                            'query' => $query,
                        ]
                    ]));
                }
            }
        }
        return empty($models) ? [] : $models;
    }

    /**
     * получить модель из API
     * @param $id
     * @return static|null
     */
    public static function findOne ($id) {
        if ($attributes = static::getRecord($id)) {
            if (($model = static::createModel($attributes)) && ($model instanceof static)) {
                $model->trigger(static::EVENT_AFTER_FIND, Yii::createObject([
                    'class' => ModelEvent::class,
                    'data' => [
                        'id' => $id,
                    ]
                ]));
            }
            return $model;
        }
        return null;
    }

    /**
     * JSON массив со всеми записями
     * @param array $query
     * @return array|bool
     */
    public static function getAllRecords (array $query = []) {
        return false;
    }

    /**
     * JSON с одной записью
     * @param $id
     * @return array|bool
     */
    public static function getRecord ($id) {
        return false;
    }

    /** @return bool */
    public function save(){
        throw new NotSupportedException('Method ' . __METHOD__ . ' not supported');
    }

    /** @return bool */
    public function delete (){
        throw new NotSupportedException('Method ' . __METHOD__ . ' not supported');
    }

    /** @return ApiClient */
    public static function getHttpClient ($config = []) {
        $client = Yii::$app->get(static::HTTP_CLIENT_COMPONENT, false);
        if (!$client instanceof ApiClient)
            throw new InvalidConfigException('HTTP client not configured');

        if (!empty($config))
            Yii::configure($client, $config);
        return $client;
    }

    /** @return ApiClient */
    public function getClient ($config = []) {
        if ($this->_httpClient instanceof ApiClient)
            return $this->_httpClient;
        return $this->_httpClient = static::getHttpClient($config);
    }

    /**
     * прикрепить http-клиент
     * @param ApiClient $client
     * @return static
     */
    public function setClient (ApiClient $client) {
        $this->_httpClient = $client;
        return $this;
    }
}