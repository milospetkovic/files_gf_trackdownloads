<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\FilesGFTrackDownloads\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
//return [
//    'routes' => [
//	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
//	   ['name' => 'page#do_echo', 'url' => '/echo', 'verb' => 'POST'],
//    ]
//];

/**
 * @var $this \OCP\Route\IRouter
 **/
//\OC_Mount_Config::$app->registerRoutes(
//    $this,
//    [
//        'resources' => [
//            'global_storages' => ['url' => '/globalstorages'],
//            'user_storages' => ['url' => '/userstorages'],
//            'user_global_storages' => ['url' => '/userglobalstorages'],
//        ],
//        'routes' => [
//            [
//                'name' => 'Ajax#getSshKeys',
//                'url' => '/ajax/public_key.php',
//                'verb' => 'POST',
//                'requirements' => [],
//            ],
//            [
//                'name' => 'Ajax#saveGlobalCredentials',
//                'url' => '/globalcredentials',
//                'verb' => 'POST',
//            ],
//        ],
//        'ocs' => [
//            [
//                'name' => 'Api#getUserMounts',
//                'url' => '/api/v1/mounts',
//                'verb' => 'GET',
//            ],
//        ],
//    ]
//);

$this->create('files_gf_trackdownloads', 'ajax/confirm.php')
    ->actionInclude('files_gf_trackdownloads/ajax/confirm.php');