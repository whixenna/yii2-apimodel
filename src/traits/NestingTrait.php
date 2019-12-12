<?php
namespace whixenna\apimodel\traits;

/**
 * Trait NestingTrait
 * методы для установления связей API-модели с другими моделями
 * - создать модель текущего класса
 * - добавить связанные модели
 * - получить связанные модели как атрибуты
 * - установить и получить родительскую модель
 *
 * @method getSafeAttributes @return string[] - реализовать метод для присвоения атрибутов без проверки
 */
trait NestingTrait {
    private $_parent;
    private $_related = [];
    private $_relatedRaw = [];

    /**
     * преобразовать JSON в модель
     * @param array $attributes
     * @param string $scenario
     * @return \yii\base\Model
     */
    public static function createModel (array $attributes, $scenario = null) {
        /** @var \yii\base\Model $model */
        $model = \Yii::createObject(static::class);
        if ($scenario)
            $model->scenario = $scenario;
        $model->setAttributes($attributes);
        return $model;
    }

    public static function primaryKey() {
        return false;
    }

    /**
     * получить массив имен классов связанных моделей
     * @return array
     */
    public function getRelations() {
        return [/* name => className */];
    }

    /**
     * добавить связанную модель или добавить в массив моделей
     * @param string $name
     * @param mixed $value
     * @param bool $asArray
     */
    public function setRelation ($name, $value, $asArray = false) {
        if (!$asArray) {
            $this->_related[$name] = $value;
        } else if ($value) {
            $this->_related[$name][] = $value;
        }
    }

    /**
     * предусмотрена связь
     * @param string $name
     * @return bool
     */
    public function hasRelation ($name) {
        $relations = $this->getRelations();
        return array_key_exists($name, $relations) && $relations[$name] !== false;
    }

    /**
     * есть связанные модели
     * @param string $name
     * @return bool
     */
    public function hasRelated ($name) {
        if (!$this->hasRelation($name))
            return false;
        return !empty($this->_related[$name]);
    }

    /**
     * использовать вместо стандартного __get() установку атрибутов и создание связанных моделей
     * @param array $values
     * @return array $values - вернуть оставшиеся простые атрибуты
     */
    public function setNestedAttributes (array $values) {
        //связанные модели
        foreach ($this->getRelations() as $relName => $className) {
            if (isset($values[$relName]) && is_array($values[$relName])) {
                //убрать предыдущие значения
                if (isset($this->_related[$relName]))
                    unset($this->_related[$relName]);

                //не создавать связь
                if (!$className) {
                    unset($values[$relName]);
                    continue;
                }
                $isModel = count(array_filter(array_keys($values[$relName]), 'is_string'));
                if ($isModel) {
                    /**
                     * единичная модель
                     * @var self $model
                     */
                    $this->_relatedRaw[$relName] = $values[$relName];
                    if ($model = @call_user_func("$className::createModel", $values[$relName])) {
                        $model->setParent($this);
                        $this->setRelation($relName, $model);
                    }
                } else {
                    //массив моделей
                    $created = [];
                    foreach ($values[$relName] as $attributes) {
                        $this->_relatedRaw[$relName][] = $attributes;

                        //не создавать дубликаты
                        $pk = @call_user_func("$className::primaryKey");
                        $hasPrimary = $pk && isset($attributes[$pk]);
                        if ($hasPrimary && in_array($attributes[$pk], $created))
                            continue;

                        //создать модель
                        if ($relModel = @call_user_func("$className::createModel", $attributes)) {
                            if ($hasPrimary)
                                $created[] = $relModel->$pk;
                            $relModel->setParent($this);
                            $this->setRelation($relName, $relModel, true);
                        }
                    }
                }
                unset($values[$relName]);
            }
        }

        //атрибуты без проверки
        if (method_exists($this, 'getSafeAttributes') && is_array($safe = $this->getSafeAttributes())) {
            foreach ($safe as $attribute) {
                if (isset($values[$attribute]))
                    parent::setAttributes([$attribute => $values[$attribute]], true);
                unset($values[$attribute]);
            }
        }

        //атрибуты с методом
        foreach ($values as $attribute => $value) {
            $methodName = 'set' . ucfirst($attribute);
            if (method_exists($this, $methodName)) {
                call_user_func([$this, $methodName], $value);
                unset($values[$attribute]);
            }
        }

        //простые атрибуты
        return $values;
    }

    /**
     * использовать вместо стандартного __get() возврат атрибутов и связанных моделей
     * @param string $name
     * @return mixed|null
     */
    public function getNestedAttributes (string $name) {
        $relAttributes = array_keys($this->getRelations());

        //обычные атрибуты
        if (!in_array($name, $relAttributes))
            return parent::__get($name);

        //связанные модели
        if (array_key_exists($name, $this->_related) && isset($this->_related[$name])) {
            return $this->_related[$name];
        }

        //есть метод
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            $relation = call_user_func([$this, $getter]);
            if ($relation && !isset($this->_related[$name])) {
                $this->_related[$name] = $relation;
            }
            return $relation;
        }
        return null;
    }

    /**
     * установить родительскую модель
     * @param object $parent
     */
    public function setParent ($parent) {
        if (!is_object($parent))
            return;
        $this->_parent = $parent;
        $parentClass = get_class($parent);
        foreach ($this->getRelations() as $relName => $className) {
            if ($parentClass == $className)
                $this->setRelation($relName, $parent);
        }
    }

    /**
     * получить родительскую модель
     * @return self|null
     */
    public function getParent() {
        return $this->_parent;
    }


    /**
     * получить связанную модель
     * @param string $name
     * @param string|integer $id
     * @param boolean $cached - не осуществлять принудительный поиск
     * @return \yii\base\Model|null
     */
    public function getRelationModel ($name, $id = null, $cached = true) {
        $relations = $this->getRelations();
        if (!isset($relations[$name]) || empty($class = $relations[$name]))
            return null;

        try {
            if ($cached && isset($this->_related[$name])) {
                $model = $this->_related[$name];
                if ($model === false)
                    return null;
                if ($id) {
                    $pk = $model::primaryKey();
                    if ($pk === false || $pk === $id)
                        return $model;
                    else
                        return null;
                }
                return $model;
            }
            if ($id && $model = @call_user_func("$class::findOne", $id)) {
                $this->setRelation($name, $model);
            } else {
                $this->setRelation($name, false);
            }
            return $model;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * получить массив связанных моделей
     * @param string $name
     * @param array $query
     * @param boolean $cached - не осуществлять принудительный поиск
     * @return \yii\base\Model[]|null
     */
    public function getRelationModels ($name, array $query = [], $cached = true) {
        $relations = $this->getRelations();
        if (!isset($relations[$name]) || empty($class = $relations[$name]))
            return null;

        try {
            if ($cached && isset($this->_related[$name])) {
                $models = $this->_related[$name];
                if ($models === false)
                    return null;
            }
            if ($models = @call_user_func("$class::findAll", $query)) {
                $this->setRelation($name, $models);
            } else {
                $this->setRelation($name, false);
            }
            return $models;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * массив параметров связи, как он пришел с сервера из JSON
     * @param $name
     * @return mixed|null
     */
    public function getRawRelation ($name) {
        if (array_key_exists($name, $this->_relatedRaw)) {
            return $this->_relatedRaw[$name];
        }
        return null;
    }
}