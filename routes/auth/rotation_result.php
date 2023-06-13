<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api->get(
     '/rotation_result',
     [
          'action' => '',         
          'uses'   => 'RotationResultController@search',
     ]
);

$api->get(
     '/rotation_result/{id:[0-9]+}',
     [
          'action' => '',         
          'uses'   => 'RotationResultController@view',
     ]
);

$api->post(
     '/rotation_result',
     [
          'action' => '',
          'uses'   => 'RotationResultController@create',
     ]
);

$api->put(
     '/rotation_result/{id:[0-9]+}',
     [
          'action' => '',
          'uses'   => 'RotationResultController@update',
     ]
);

$api->delete(
     '/rotation_result/{id:[0-9]+}',
     [
          'action' => '',
          'uses'   => 'RotationResultController@delete',
     ]
);
