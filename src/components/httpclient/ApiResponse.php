<?php
namespace whixenna\apimodel\components\httpclient;

use yii\httpclient\Client;
use yii\httpclient\Response;

class ApiResponse extends Response {
    const STATUS_OK = 'ok';
    const STATUS_ERROR = 'error';

    /** @inheritdoc */
    public function getIsOk ($checkBody = true) {
        if (!$isOk = parent::getIsOk())
            return false;
        else if (!$checkBody)
            return $isOk;
        else {
            return $this->getData('status') == self::STATUS_OK;
        }
    }

    /**
     * @param null $key
     * @return mixed|null
     */
    public function getData ($key = null) {
        $data = [];
        try {
            if ($this->detectFormatByContent($content = $this->getContent()) != Client::FORMAT_JSON) {
                $data['status'] = self::STATUS_ERROR;
                if ($title = preg_match('/<title>(.*)</title>/s', $content, $matches) && isset($matches[1])) {
                    $data['message'] = $matches[1];
                } else {
                    $data['message'] = 'Incorrect response format';
                }
            } else {
                $data = parent::getData();
                $status = isset($data['status']) ? $data['status'] : self::STATUS_ERROR;

                if ($status !== self::STATUS_OK) {
                    $message = isset($data['message']) && !empty($data['message']) ? $data['message'] : "status $status";
                    $data['message'] = "API error: $message";
                    return $data;
                }
            }
        } catch (\Exception $e) {
            $data['status'] = self::STATUS_ERROR;
            $data['message'] = $e->getMessage();
        }
        return !$key ? $data : (isset($data[$key]) ? $data[$key] : null);
    }
}