<?php
namespace whixenna\apimodel\traits;

use yii\base\Model;

/**
 * список для dropdown
 */
trait SelectListTrait {
    abstract public static function primaryKey();

    /**
     * массив для стандарного селекта [id => name]
     * @param string|callable $textTemplate
     * @param string|null $valueKey
     * @param array $query
     * @return array
     */
    public static function getList ($textTemplate = self::FULL_NAME_TEMPLATE, $valueKey = null, array $query = []) {
        if (empty($valueKey))
            $valueKey = static::primaryKey();
        if ($data = static::findAll($query))
            return static::formList($data, $valueKey, $textTemplate);
        return [];
    }

    /**
     * массив для стандарного AJAX Select2 [id, text][]
     * @param string|callable $textTemplate
     * @param string|null $valueKey
     * @param array $query
     * @return array
     */
    public static function getSelect2List ($textTemplate = self::FULL_NAME_TEMPLATE, $valueKey = null, array $query = []) {
        $defaultList = call_user_func_array('static::getList', func_get_args());
        $list = [];
        if ($defaultList) {
            foreach ($defaultList as $id => $name)
                $list[] = ['id' => $id, 'text' => $name];
        }
        return $list;
    }

    /**
     * массив для стандарного AJAX Select2 [id, text][] сформировать из готовых моделей
     * @param \shelfmatchView\models\interfaces\ApiModelInterface[]|array[] $models
     * @param string|callable $textTemplate
     * @param string|null $valueKey
     * @return array
     */
    public static function modelsToSelect2List (array $models, $textTemplate = self::FULL_NAME_TEMPLATE, $valueKey = null) {
        $list = [];
        foreach (static::formList($models, $valueKey, $textTemplate) as $id => $name)
            $list[] = ['id' => $id, 'text' => $name];
        return $list;
    }

    /**
     * @param Model[]|array[] $items
     * @param string $valueKey
     * @param string|callable $textTemplate
     * @return array [value => text]
     */
    public static function formList (array $items, $valueKey, $textTemplate) {
        $list = [];
        foreach ($items as $model) {
            if ($model instanceof Model) {
                $attributes = $model->attributes;
                $key = $model->$valueKey;
            } else if (is_array($model)) {
                $attributes = $model;
                $key = $model[$valueKey];
            } else
                continue;
            $list[$key] = is_callable($textTemplate)
                ? call_user_func($textTemplate, $attributes)
                : \Yii::t('app', $textTemplate, $attributes);
        }
        return $list;
    }
}