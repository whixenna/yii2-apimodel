<?php
namespace whixenna\apimodel\traits;

use yii\validators\BooleanValidator;
use yii\validators\SafeValidator;

/**
 * получение и проверка атрибутов модели
 */
trait AttributesTrait {
    /**
     * получение атрибутов валидатора
     * @param string $className
     * @return array
     */
    public function getValidatorAttributes ($className) {
        $attributes = [];
        foreach ($this->validators as $validator) {
            if ($validator instanceof $className)
                $attributes += $validator->attributes;
        }
        return $attributes;
    }

    /**
     * проверяется ли атрибут валидатором
     * @param string $attribute
     * @param string $className
     * @return boolean
     */
    public function isValidatorAttribute ($attribute, $className) {
        foreach ($this->validators as $validator) {
            if ($validator instanceof $className && in_array($attribute, $validator->attributes))
                return true;
        }
        return false;
    }

    /** @return array */
    public function getBooleanAttributes() {
        return $this->getValidatorAttributes(BooleanValidator::class);
    }

    /** @return array */
    public function getSafeAttributes() {
        return $this->getValidatorAttributes(SafeValidator::class);
    }

    /**
     * получить только заполненные атрибуты со значениями
     * @return array
     */
    public function filterFilledAttributes() {
        return array_filter($this->attributes, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * получить необходимые заполненные атрибуты со значениями
     * @param array $needed
     * @return array
     */
    public function getFilledAttributes ($needed = []) {
        $attributes = [];
        if (empty($needed))
            $needed = array_keys($this->attributes);
        $booleans = $this->getBooleanAttributes();

        foreach ($needed as $attr) {
            if (!isset($this->$attr))
                continue;
            if (in_array($attr, $booleans))
                $attributes[$attr] = !!(int)$this->$attr;
            else
                $attributes[$attr] = $this->$attr;
        }
        return $attributes;
    }
}