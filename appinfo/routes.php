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

	   ['name' => 'Users#api', 'url' => '/', 'verb' => 'GET'],
		/*all users:<server>/ocs/v2.php/apps/virwork_api/users?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'Users#getUsers', 'url' => '/users', 'verb' => 'GET'],
		/*all users:<server>/ocs/v2.php/apps/virwork_api/users/details/{username}?operation_code=virwork_cloudstorage_account*/

	   ['name' => 'Users#getUserDetails', 'url' => '/users/details/{username}', 'verb' => 'GET'],



	   ['name' => 'Users#enableUser', 'url' => '/users/enable/{username}', 'verb' => 'GET'],


	   ['name' => 'Users#disableUser', 'url' => '/users/disable/{username}', 'verb' => 'GET'],

		/*all users:<server>/ocs/v2.php/apps/virwork_api/users/fillStorageInfo/{username}?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'Users#fillStorageInfo', 'url' => '/users/storage_info/{username}', 'verb' => 'GET'],

	   
	   /*all groups:<server>/ocs/v2.php/apps/virwork_api/groups?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'Users#getGroups', 'url' => '/groups', 'verb' => 'GET'],
	   /*all groups details:<server>/ocs/v2.php/apps/virwork_api/details?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'Users#getGroupsDetails', 'url' => '/groups/details', 'verb' => 'GET'],
	   /*a group details: <server>/ocs/v2.php/apps/virwork_api/group?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['name' => 'Users#getGroup', 'url' => '/groups/group/users', 'verb' => 'GET'],
	   /*a group all user details:<server>/ocs/v2.php/apps/virwork_api/details?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['name' => 'Users#getGroupUsersDetails', 'url' => '/groups/group/users/details', 'verb' => 'GET'],
      
	   ['name' => 'Users#addUser', 'url' => '/users/add', 'verb' => 'POST'],


		['name' => 'Users#enableUser', 'url' => '/users/{userId}/enable', 'verb' => 'GET'],
		['name' => 'Users#disableUser', 'url' => '/users/{userId}/disable', 'verb' => 'GET'],

	   ['name' => 'Users#getUserPreferencesValues', 'url' => '/users/files_permission/{username}', 'verb' => 'GET'],

	   
	   ['name' => 'Users#getUserPreferencesValuesByUserNameAndFileId', 'url' => '/users/files_permission/{username}/{fileid}', 'verb' => 'GET'],
	   ['name' => 'Users#deleteUserValue', 'url' => '/users/files_permission/deleteUserValue', 'verb' => 'POST'],

  	   ['name' => 'Users#addToGroup', 'url' => '/groups/addToGroup', 'verb' => 'POST'],



		/*a group all user details:<server>/ocs/v2.php/apps/virwork_api/groups/add?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'Users#addGroups', 'url' => '/groups/add', 'verb' => 'POST'],


	   ['name' => 'VirworkLogin#api', 'url' => '/auths', 'verb' => 'GET'],
        // remember:sleep | start
	   ['name' => 'VirworkLogin#tryPostLogin', 'url' => '/auths/login', 'verb' => 'POST'],
	   //http://192.168.178.158/nextcloud/ocs/v2.php/apps/virwork_api/auths/action/ASDZXCASD/admin/UIPtQI69icxATVRJek5EVTI=?/ae34cf9e7ca0e62ab4c689fcdb8001ee62a646a23c15bb3d14fe11ddade8f4cc0?auth_url=http://192.168.178.158:8080
	   ///auths/action/{remember}/{user}/{password}/{token}
	   ['name' => 'VirworkLogin#tryGetLogin', 'url' => '/auths/action/{remember}/{user}/{password}/{token}', 'verb' => 'GET'],

	   ['name' => 'VirworkLogin#logout', 'url' => '/auths/out/{username}', 'verb' => 'GET'],

       // System Config

	   ['name' => 'VirworkSystemConfig#getAndroidVersion', 'url' => '/system_config/android/version', 'verb' => 'GET'],

	   ['name' => 'VirworkSystemConfig#getIOSVersion', 'url' => '/system_config/ios/version', 'verb' => 'GET'],

        /*ip/nextcloud/ocs/v2.php/apps/virwork_api/system_config/client_information*/
	   ['name' => 'VirworkSystemConfig#getClientInformation', 'url' => '/system_config/client_information', 'verb' => 'GET'],
       /*ip/nextcloud/ocs/v2.php/apps/virwork_api/system_config/update_client_information*/
	   ['name' => 'VirworkSystemConfig#setClientInformation', 'url' => '/system_config/update_client_information', 'verb' => 'POST'],

	   ['name' => 'VirworkSystemConfig#updateStylesheet', 'url' => '/system_config/updateStylesheet', 'verb' => 'POST'],
	   ['name' => 'VirworkSystemConfig#uploadImage', 'url' => '/system_config/uploadImage', 'verb' => 'POST'],

       // Notification

		/*all users:<server>/ocs/v2.php/apps/virwork_api/notifications?operation_code=virwork_cloudstorage_account*/
	   ['name' => 'VirworkNotification#getNotifications', 'url' => '/notifications', 'verb' => 'GET'],

	],
    'routes' => [

	   /*all users:index.php/apps/virwork_api/users?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getUsers', 'url' => '/users', 'verb' => 'GET'],
	   
	 
	   /*all groups:index.php/apps/virwork_api/groups?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getGroups', 'url' => '/groups', 'verb' => 'GET'],
	   /*all groups details:index.php/apps/virwork_api/groups/details?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#getGroupsDetails', 'url' => '/groups/details', 'verb' => 'GET'],
	   /*a group details: index.php/apps/virwork_api/groups/group?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['root' => '/virwork','name' => 'users#getGroup', 'url' => '/groups/group/users', 'verb' => 'GET'],
	   /*a group all user details:index.php/apps/virwork_api/groups/group/users/details?operation_code=virwork_cloudstorage_account&groupId=测试部*/
	   ['root' => '/virwork','name' => 'users#getGroupUsersDetails', 'url' => '/groups/group/users/details', 'verb' => 'GET'],

	   ['root' => '/virwork','name' => 'users#addUser', 'url' => '/users/add', 'verb' => 'POST'],
		/*a group all user details:index.php/apps/virwork_api/groups/add?operation_code=virwork_cloudstorage_account*/
	   ['root' => '/virwork','name' => 'users#addGroup', 'url' => '/groups/add', 'verb' => 'POST'],


    ]
];
