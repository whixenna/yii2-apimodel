<?php
namespace app\models;

use whixenna\apimodel\models\RelationModel;
use app\models\ExampleApiModel;
use app\models\ExampleRelRelationModel;

/**
 * пример связанной модели 2го уровня
 *
 * @property string $id
 * @property string $name
 * @property ExampleApiModel $parent
 * @property ExampleRelRelationModel $child
 */
class ExampleRelationModel extends RelationModel {
    public $id;
    public $name;
    public $parentId;

    public function getRelations() {
        return [
            'parent'    => 'app\models\ExampleApiModel',
            'childs'    => 'app\models\ExampleRelRelationModel',
        ];
    }

    public function rules() {
        return [
            [['id', 'name', 'parentId'], 'string'],
        ];
    }

    public function attributeLabels() {
        return [
            'id'        => 'ID',
            'name'      => 'Name',
            'parent'    => 'Parent',
            'childs'     => 'Childs',
        ];
    }

    /** @return string */
    public static function primaryKey() {
        return 'id';
    }

    /** @return ExampleApiModel|null */
    public function getParent() {
        return $this->getRelationModel('parent', $this->parentId);
    }
}