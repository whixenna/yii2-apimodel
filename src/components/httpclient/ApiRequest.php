<?php
namespace whixenna\apimodel\components\httpclient;

use yii\httpclient\Client;
use yii\httpclient\Request;

class ApiRequest extends Request {
    public function jsonFormat() {
        $this->format = Client::FORMAT_JSON;
        $this->setHeaders(['content-type' => 'application/json', 'accept' => 'application/json']);
        return $this;
    }
}