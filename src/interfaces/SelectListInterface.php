<?php
namespace whixenna\apimodel\interfaces;

/**
 * Interface SelectListInterface
 * реализовать получение списка для dropdown
 */
interface SelectListInterface {
    /**
     * получить список [id => value]
     * @return array
     */
    public static function getList($textTemplate, $valueKey = null, array $query = []);

    /**
     * получить список Select2 [id, text][]
     * @return array
     */
    public static function getSelect2List ($textTemplate, $valueKey = null, array $query = []);
}