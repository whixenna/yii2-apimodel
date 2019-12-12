<?php
return [
    /* ... */
    'components' => [
        /* ... */

        //наименование компонента из whixenna\apimodel\models\ApiModel::HTTP_CLIENT_COMPONENT
        'apiHttpClient' => [
            'class' => 'whixenna\apimodel\components\httpclient\ApiClient',
            'baseUrl' => 'https://localhost::8080/myapi',
        ],
        /* ... */
    ],
    /* ... */
];