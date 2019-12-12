<?php
namespace whixenna\apimodel\components\httpclient;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\Exception as ClientException;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use whixenna\apimodel\components\httpclient\ApiRequest;
use whixenna\apimodel\components\httpclient\ApiResponse;

/**
 * Class ApiClient
 * @property string $baseUrl - URL or Yii param 'serviceUrl'
 * @property integer $requestTimeoutSec
 */
class ApiClient extends Client {
    public $baseUrl;
    public $requestTimeoutSec = 30;
    public $detailedError = false;

    const FORBIDDEN_CODES = [401, 403, 423];

    public function init() {
        parent::init();
        $requestConfig = ['class' => ApiRequest::class];
        if (isset($this->requestTimeoutSec))
            $requestConfig['options']['timeout'] = $this->requestTimeoutSec;

        $this->requestConfig = array_merge($requestConfig, $this->requestConfig);
        $this->responseConfig = array_merge(['class' => ApiResponse::class], $this->responseConfig);

        //API URL
        if (!isset($this->baseUrl)) {
            throw new InvalidConfigException('API client base URL must be configured');
        }
    }

    /**
     * @param string|array $url - url with query params for setUrl method
     * @param string $method
     * @param array|null $data - POST data
     * @param boolean|string $token - взять для текущего пользователя или задать произвольный
     * @param boolean $jsonFormat
     * @return \yii\httpclient\Request
     */
    public function createApiRequest ($url, $method, $data = null, $token = true, $jsonFormat = true) {
        if (!is_array($url))
            $url = [$url];

        //прикрепить токен
        if ($token) {
            if (is_string($token))
                $url['token'] = $token;
            else if (Yii::$app->user->identity && isset(Yii::$app->user->identity->token)) {
                $url['token'] = Yii::$app->user->identity->token;
            }
        }

        $request = (parent::createRequest())
            ->setUrl($url)
            ->setMethod($method);
        if ($request instanceof ApiRequest && $jsonFormat)
            $request->jsonFormat();

        if (is_array($data))
            $request->setData($data);
        return $request;
    }

    /**
     * @param \yii\httpclient\Request $request
     * @return \yii\httpclient\Response
     * @throws ForbiddenHttpException
     * @throws HttpException
     * @throws \yii\base\ExitException
     * @throws \yii\httpclient\Exception
     */
    public function send ($request) {
        try {
            $response = parent::send($request);
            $isJson = $response->getFormat() == \yii\httpclient\Client::FORMAT_JSON;

            if (!$response->isOk) {
                $code = $response->getStatusCode();
                switch (true) {
                    case empty($code):
                        throw new HttpException(503, Yii::t('apimodel', 'API server not responding'));
                    case in_array((int)$code, self::FORBIDDEN_CODES):
                        throw new ForbiddenHttpException(Yii::t('apimodel', 'API access denied'));
                        break;
                    case !$isJson:
                        $error = self::readApiError($code, $response->getContent(), $this->detailedError);
                        throw new HttpException($code,
                            Yii::t('apimodel', 'API request failed with code {0}', ["$code: {$error['message']}"]));
                        break;
                    default:
                        $message = !empty($data = $response->getData() && isset($data['message']) && !empty($data['message']))
                            ? $code . ': ' . $data['message']
                            : $code;
                        throw new HttpException($code,
                            Yii::t('apimodel', 'API request failed with code {0}', [$message]));
                }
            }
        } catch (\Exception $e) {
            if ($e instanceof ClientException && strpos($e->getMessage(), 'fopen') !== false) {
                throw new HttpException(503, Yii::t('apimodel', 'API server not responding'));
            }
            throw $e;
        }
        return $response;
    }

    /** @return array [message, details] */
    protected static function readApiError ($code, $content, $detailed = false) {
        $result = ['message' => 'Incorrect response format'];

        //if tomcat returns HTML error page
        if (preg_match('/\<title\>(.+)\<\/title\>.*\<body\>(.+)\<\/body\>/s', $content, $matches)) {
            if (!empty($matches = array_slice($matches, 1))) {
                $result['message'] = \Yii::t('apimodel', 'API request failed with code {0}', [$code . ': ' . $matches[0]]) ;
                if ($detailed && isset($matches[1]))
                    $result['details'] = strip_tags($matches[1]);
                return $result;
            }
        }
        return $result;
    }
}