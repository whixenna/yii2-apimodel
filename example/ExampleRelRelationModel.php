<?php
namespace app\models;

use whixenna\apimodel\models\RelationModel;
use app\models\ExampleRelationModel;

/**
 * пример связанной модели 3го уровня
 *
 * @property string $nounique
 * @property string $name
 * @property ExampleRelationModel $parent
 */
class ExampleRelRelationModel extends RelationModel {
    public $nounique;
    public $name;

    public function rules() {
        return [
            [['nounique', 'name'], 'string'],
        ];
    }

    public function attributeLabels() {
        return [
            'nounique'  => 'Attribute',
            'name'      => 'Name',
        ];
    }
}