<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#users -> OCA\VirWork\Controller\UsersController->getUsers()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'ocs' =>[

	   ['name' => 'users#api', 'url' => '/', 'verb' => 'GET'],
		/*all users:<server>/ocs/v2.php/apps/virwork_api/users?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'users#getUsers', 'url' => '/users', 'verb' => 'GET'],
	   ['name' => 'users#getUserAuthInfo', 'url' => '/user_auth', 'verb' => 'GET'],
	   ['name' => 'users#getUserAuthInfos', 'url' => '/user_auths', 'verb' => 'GET'],
	   ['name' => 'users#saveVirworkAuth', 'url' => '/user_auth/save', 'verb' => 'GET'],
	   /*all groups:<server>/ocs/v2.php/apps/virwork_api/groups?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'users#getGroups', 'url' => '/groups', 'verb' => 'GET'],
	   /*all groups details:<server>/ocs/v2.php/apps/virwork_api/details?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'users#getGroupsDetails', 'url' => '/groups/details', 'verb' => 'GET'],
	   /*a group details: <server>/ocs/v2.php/apps/virwork_api/group?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['name' => 'users#getGroup', 'url' => '/groups/group/users', 'verb' => 'GET'],
	   /*a group all user details:<server>/ocs/v2.php/apps/virwork_api/details?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['name' => 'users#getGroupUsersDetails', 'url' => '/groups/group/users/details', 'verb' => 'GET'],

	   ['name' => 'users#addUser', 'url' => '/users/add', 'verb' => 'GET'],
		/*a group all user details:<server>/ocs/v2.php/apps/virwork_api/add?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'users#addGroup', 'url' => '/groups/add', 'verb' => 'POST'],
	],
    'routes' => [

	   /*all users:index.php/apps/virwork_api/users?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getUsers', 'url' => '/users', 'verb' => 'GET'],
	   
	   ['root' => '/virwork','name' => 'users#getUserAuthInfos', 'url' => '/user_auth', 'verb' => 'GET'],
	   /*all groups:index.php/apps/virwork_api/groups?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getGroups', 'url' => '/groups', 'verb' => 'GET'],
	   /*all groups details:index.php/apps/virwork_api/groups/details?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getGroupsDetails', 'url' => '/groups/details', 'verb' => 'GET'],
	   /*a group details: index.php/apps/virwork_api/groups/group?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['root' => '/virwork','name' => 'users#getGroup', 'url' => '/groups/group/users', 'verb' => 'GET'],
	   /*a group all user details:index.php/apps/virwork_api/groups/group/users/details?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['root' => '/virwork','name' => 'users#getGroupUsersDetails', 'url' => '/groups/group/users/details', 'verb' => 'GET'],

	   ['root' => '/virwork','name' => 'users#addUser', 'url' => '/users/add', 'verb' => 'GET'],
		/*a group all user details:index.php/apps/virwork_api/groups/add?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#addGroup', 'url' => '/groups/add', 'verb' => 'POST'],


    ]
];
