<?php
namespace whixenna\apimodel\interfaces;

/**
 * Interface ApiModelInterface
 * реализовать возможности модели аналогично ActiveRecord через API
 */
interface ApiModelInterface {
    /**
     * уникальный атрибут
     * @return string
     */
    public static function primaryKey();

    /**
     * получить массив JSON-атрибутов по критериям из API
     * @param array $query - массив ключ-значение для GET-запроса
     * @return array[]
     */
    public static function getAllRecords(array $query = []);

    /**
     * получить массив моделей по критериям из API
     * @param array $query - массив ключ-значение для GET-запроса
     * @return static[]
     */
    public static function findAll($query);

    /**
     * получить атрибуты модели из JSON по ID из API
     * @param string $id
     * @return array
     */
    public static function getRecord($id);

    /**
     * получить модель по ID из API
     * @param string $id
     * @return \yii\base\Model
     */
    public static function findOne($id);

    /**
     * найти связанный клиент для API
     * @return \yii\httpclient\Client
     */
    public static function getHttpClient();

    /**
     * получить массив связанных моделей
     * @return array
     */
    public function getRelations();

    /**
     * пролучить предыдущие значения измененных атрибутов
     * @return array
     */
    public function getOldAttributes();

    /**
     * получить единичную связанную модель по ID
     * @param string $name - имя связи
     * @param string $id - ID связанной модели
     * @return string
     */
    public function getRelationModel($name, $id);

    /**
     * получить массив связанных моделей по критериям
     * @param string $name - имя связи
     * @param array $query - массив ключ-значение для GET-запроса
     * @return string
     */
    public function getRelationModels($name, array $query = []);

    /**
     * задана связь с таким именем
     * метод должен возвращать массив ['attribute' => 'namespace\Class'] связанных моделей,
     * которые будут созданы из массива, загруженного в поле attribute, с использованием указанного класса,
     * единичная модель для ассоциативного массива и массив моделей для числового, где подмассивы
     * будут восприняты как единичные модели.
     * если вместо имени класса указать 'attribute' => false, связанные модели не будут созданы.
     * @param string $name - имя связи
     * @return boolean
     */
    public function hasRelation($name);

    /**
     * запрос на сохранение модели
     * @return string
     */
    public function save();

    /**
     * запрос на удаление модели
     * @return string
     */
    public function delete();

    /**
     * получить связанный клиент для API
     * @return \yii\httpclient\Client
     */
    public function getClient();
}