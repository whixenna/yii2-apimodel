<?php
namespace whixenna\apimodel\models;

use yii\base\Model;
use whixenna\apimodel\traits\AttributesTrait;
use whixenna\apimodel\traits\NestingTrait;
use whixenna\apimodel\traits\JsonAttributesTrait;

/**
 * Class RelationModel
 *
 * при необходимости задать наследнику свойства:
 * @method ->getRelations() - метод должен возвращать массив ['attribute' => 'namespace\Class'] связанных моделей,
 * которые будут созданы из массива, загруженного в поле attribute, с использованием указанного класса,
 * единичная модель для ассоциативного массива и массив моделей для числового, где подмассивы
 * будут восприняты как единичные модели.
 * если вместо имени класса указать 'attribute' => false, связанные модели не будут созданы.
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
abstract class RelationModel extends Model {
    use AttributesTrait,
        NestingTrait,
        JsonAttributesTrait {
            JsonAttributesTrait::getBooleanAttributes insteadof AttributesTrait;
        }

    protected $_oldAttributes;

    const EVENT_AFTER_INIT = 'afterInit';

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

        $this->trigger(self::EVENT_AFTER_INIT);
    }

    /** @return array|null */
    public function getOldAttributes() {
        return $this->_oldAttributes;
    }
}