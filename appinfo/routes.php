<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\FilesGFTrackDownloads\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        [ 'name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        [ 'name' => 'page#yourconfirmedfiles', 'url' => '/yourconfirmedfiles', 'verb' => 'GET'],
        [ 'name' => 'page#yoursharednotconfirmed', 'url' => '/yoursharednotconfirmed', 'verb' => 'GET'],
        [ 'name' => 'page#yoursharedandconfirmed', 'url' => '/yoursharedandconfirmed', 'verb' => 'GET'],
        [ 'name' => 'action#confirm', 'url' => 'ajax/confirm.php', 'verb' => 'POST' ],
        [ 'name' => 'action#confirmSelectedSharedFiles', 'url' => 'ajax/confirmSelectedSharedFiles.php', 'verb' => 'POST' ]
    ]
];