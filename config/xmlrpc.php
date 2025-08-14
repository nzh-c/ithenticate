<?php

return [
    'xml_rpc_url' => env('ITHENTICATE_XML_RPC_URL','https://api.ithenticate.com/rpc'),
    'timeout' => env('ITHENTICATE_TIMEOUT'),
    'group_name' => env('ITHENTICATE_GROUP_NAME'),
    'submit_to' => env('ITHENTICATE_SUBMIT_TO'),
    'callback_url' => env('ITHENTICATE_CALLBACK_URL'),

    'd_username' => env('ITHENTICATE_D_USERNAME'),
    'd_password' => env('ITHENTICATE_D_PASSWORD'),
];

