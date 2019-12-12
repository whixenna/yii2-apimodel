<?php
namespace whixenna\apimodel\traits;

use yii\base\Model;

/**
 * Trait JsonAttributesTrait
 * методы для рекурсивного преобразования JSON в модель и массивы связанных моделей
 */
trait JsonAttributesTrait {
    /** @return array */
    abstract public function getAttributes ($names = null, $except = []);

    /** @return Model[] */
    abstract public function getRelations();

    /**
     * необходимо переопределить метод в классе для верного преобразования текстовых 'true'/'false'
     * @return array
     */
    public function getBooleanAttributes() {
        return [];
    }

    /**
     * нормализовать атрибуты
     * @param array $attributes
     * @param integer $maxDepth
     * @return array
     */
    public function normalizeAttributes ($attributes, $maxDepth = 5) {
        return self::normalize($attributes, $this->getBooleanAttributes(), $maxDepth);
    }

    /**
     * получить все атрибуты, кроме отфильтрованных, как нормализованный массив
     * @param boolean $withRelations
     * @param array $excludeFields
     * @param integer $maxDepth
     * @return array
     */
    public function fieldsToArray ($withRelations = true, $excludeFields = [], $maxDepth = 3) {
        if (--$maxDepth < 0) return null;

        //простые атрибуты
        $attributes = array_filter($this->getAttributes(), function ($field) use ($excludeFields) {
            return !in_array($field, $excludeFields);
        }, ARRAY_FILTER_USE_KEY);
        $attributes = $this->normalizeAttributes($attributes, $maxDepth);

        //атрибуты связанных моделей
        if ($withRelations) {
            foreach ($this->getRelations() as $attr => $className) {
                if (in_array($attr, $excludeFields))
                    continue;
                try {
                    $items = $this->$attr;
                    if ($items === null || $items === '' || (is_array($items) && empty($items)))
                        continue;
                    $attributes[$attr] = $this->normalizeRelation($items, $maxDepth);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $attributes;
    }

    /**
     * получить указанные атрибуты как нормализованный массив
     * @param array $fields
     * @return array
     */
    public function onlyToArray ($fields = [], $maxDepth = 3) {
        if (--$maxDepth < 0) return null;

        //простые атрибуты
        $attributes = array_filter($this->getAttributes(), function($field) use ($fields) {
            return in_array($field, $fields);
        }, ARRAY_FILTER_USE_KEY);
        $attributes = $this->normalizeAttributes($attributes, $maxDepth);

        //атрибуты связанных моделей
        foreach ($this->getRelations() as $attr => $className) {
            if (!in_array($attr, $fields))
                continue;
            try {
                $items = $this->$attr;
                if ($items === null || $items === '' || (is_array($items) && empty($items)))
                    continue;
                $attributes[$attr] = $this->normalizeRelation($items, $maxDepth);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $attributes;
    }

    /**
     * нормализовать связи
     * @param array $items
     * @param integer $maxDepth
     * @return array
     */
    protected function normalizeRelation ($items, $maxDepth) {
        if ($maxDepth < 1)
            return null;
        if (is_array($items)) {
            $newItems = [];
            foreach ($items as $item) {
                $newItems[] = $item->normalizeValue($item, false, $maxDepth);
            }
            return !empty($newItems) ? $newItems : null;
        } else {
            return self::normalizeValue($items, false, $maxDepth);
        }
    }

    /**
     * преобразование атрибутов в массив, приведенный для преобразования в JSON
     * атрибуты с пустыми значениями (null, пустая строка, пустой массив) будут удалены
     * @param array $attributes - грязные атрибуты
     * @param array $booleanAttributes
     * @param int $maxDepth
     * @return array - валидные преобразованные атрибуты
     */
    public static function normalize ($attributes, $booleanAttributes = [], $maxDepth = 3) {
        if ($maxDepth < 0)
            return null;
        $result = [];

        if (is_array($attributes)) {
            foreach ($attributes as $attr => $value) {
                if ($attr == 'files') {
                    $result[$attr] = $value;
                } else if(is_array($value) && empty($value)) {
                    continue;
                } else {
                    $result[$attr] = self::normalizeValue($value, in_array($attr, $booleanAttributes, true), $maxDepth);
                }
            }
        } else if (is_scalar($attributes)) {
            $result['id'] = (String)$attributes;
        }
        return $result;
    }

    /**
     * @param mixed $value
     * @param bool $toBoolean - строчные значения 'true', 'false' в булевы
     * @param int $maxDepth
     * @return Model|array|mixed|null
     */
    public static function normalizeValue ($value, $toBoolean = false, $maxDepth = 3) {
        switch (true) {
            case $value === '' || $value === null:
                return null;
            case is_bool($value):
                return $value;
            case $toBoolean || in_array($value, ['true', 'false'], true):
                if ($value === 'true')
                    return true;
                else if ($value === 'false')
                    return false;
                else
                    return (boolean)(int)$value;
            case is_scalar($value):
                return $value;
            case $value instanceof self:
                return $maxDepth ? $value->fieldsToArray($maxDepth, [], $maxDepth - 1) : null;
            case $value instanceof Model:
                return $value->fields();
            case is_array($value):
                return $maxDepth ? self::normalize($value, [], $maxDepth - 1) : $value;
                break;
            default:
                return $value;
        }
    }
}