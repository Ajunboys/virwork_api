<?php
namespace OCA\Virwork_API\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;


use OCP\App\IAppManager;
use OC\Accounts\AccountManager;
use OC\User\Backend;
use OC\HintException;
use OC\User\NoUserException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\NotFoundException;
use OC_Helper;

use OC\Settings\Mailer\NewUserMailHelper;
use OCA\Provisioning_API\FederatedFileSharingFactory;


use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;


class UsersController extends Controller {

	/** @var IAppManager */
	private $appManager;
	/** @var ILogger */
	private $logger;
	/** @var IFactory */
	private $l10nFactory;
	/** @var NewUserMailHelper */
	private $newUserMailHelper;
	/** @var FederatedFileSharingFactory */
	private $federatedFileSharingFactory;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var IUserManager */
	protected $userManager;
	/** @var IUserSession */
	protected $userSession;
	/** @var IConfig */
	protected $config;
	/** @var IGroupManager|\OC\Group\Manager */ // FIXME Requires a method that is not on the interface
	protected $groupManager;
	/** @var AccountManager */
	protected $accountManager;

	/** @var appName */
	protected $appName;

	/** @var OPERATION_CODE */
	const OPERATION_CODE = "virwork_cloudstorage_account";




    
	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param IAppManager $appManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param AccountManager $accountManager
	 * @param ILogger $logger
	 * @param IFactory $l10nFactory
	 * @param NewUserMailHelper $newUserMailHelper
	 * @param FederatedFileSharingFactory $federatedFileSharingFactory
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct($appName, 
		IRequest $request, 
	    IUserManager $userManager,
		IConfig $config,
		IAppManager $appManager,
		IGroupManager $groupManager,
		IUserSession $userSession,
		AccountManager $accountManager,
		ILogger $logger,
		IFactory $l10nFactory,
		NewUserMailHelper $newUserMailHelper,
		FederatedFileSharingFactory $federatedFileSharingFactory,
		ISecureRandom $secureRandom){

		parent::__construct($appName, $request);
        
		$this->appName = $appName;
		$this->appManager = $appManager;
		$this->logger = $logger;
		$this->l10nFactory = $l10nFactory;
		$this->newUserMailHelper = $newUserMailHelper;
		$this->federatedFileSharingFactory = $federatedFileSharingFactory;
		$this->secureRandom = $secureRandom;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
	}
    /**
      * @NoAdminRequired
      */
	public function index() {
        $template = new PublicTemplateResponse($this->appName, 'main', []);
        $template->setHeaderTitle('Public page');
        $template->setHeaderDetails('some details');
        $response->setHeaderActions([
            new SimpleMenuAction('download', 'Label 1', 'icon-css-class1', 'link-url', 0),
            new SimpleMenuAction('share', 'Label 2', 'icon-css-class2', 'link-url', 10),
        ]);
        return $template;
    }

    /**
	 * ip/nextcloud/index.php/apps/virwork_api
	 * returns a list of users
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */ 
    public function api(): DataResponse {
			return new DataResponse([
				'result' => true,
				'message' => 'installed virwork api.'
			   ]);
    }

    /**
     * @param string $username
	 * @param string $operation_code
	 * @return DataResponse
	 *
     * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */ 
    public function getUserDetails(string $username = '', string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		$data = [];
    	if ($username == '') {
    		return new DataResponse([
				'result' => false,
				'message' => 'username is null.',
				 'error_code' => 403
			   ]);
    	}
        
    	$data = $this->getUserData($username);

    	return new DataResponse([
				'result' => true,
				'data' => $data				
		]);
    }
  

    /**
	 * creates a array with all user data
	 *
	 * @param string $userId
	 * @return array
	 * @throws NotFoundException
	 * @throws OCSException
	 * @throws OCSNotFoundException
	 */
	protected function getUserData(string $userId = ''): array {
		
		$data = [];

		// Check if the target user exists
		$targetUserObject = $this->userManager->get($userId);
		if($targetUserObject === null) {
			throw new OCSNotFoundException('User does not exist');
		}

		
		$data['enabled'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'enabled', 'true') === 'true';

		// Get groups data
		$userAccount = $this->accountManager->getUser($targetUserObject);
		$groups = $this->groupManager->getUserGroups($targetUserObject);
		$gids = [];
		foreach ($groups as $group) {
			$gids[] = $group->getGID();
		}
        
        try {
			# might be thrown by LDAP due to handling of users disappears
			# from the external source (reasons unknown to us)
			# cf. https://github.com/nextcloud/server/issues/12991
			$data['storageLocation'] = $targetUserObject->getHome();

            $useFilesWorkDir = $targetUserObject->getHome().'/files';
			
			$files = array(); 
		    $cdir = scandir($useFilesWorkDir); 
		    foreach ($cdir as $key => $value) { 
	           if (!in_array($value,array(".",".."))) { 
	           		if (is_dir($useFilesWorkDir . '/' .$value)) {
	           			$files[] = $value; 
	           		}
			    } 
		    } 
			$data['storageInformation'] = [
				"location"=>$data['storageLocation'], 
			    "dir"=>$useFilesWorkDir, 
			    'files'=>$files
			];
		} catch (NoUserException $e) {
			throw new OCSNotFoundException($e->getMessage(), $e);
		}

		// Find the data
		$data['id'] = $targetUserObject->getUID();
		$data['lastLogin'] = $targetUserObject->getLastLogin() * 1000;
		$data['backend'] = $targetUserObject->getBackendClassName();


		$data['subadmin'] = $this->getUserSubAdminGroupsData($targetUserObject->getUID());
		$data['quota'] = $this->fillStorageInfo($targetUserObject->getUID());
		
		
		$data[AccountManager::PROPERTY_EMAIL] = $targetUserObject->getEMailAddress();
		$data[AccountManager::PROPERTY_DISPLAYNAME] = $targetUserObject->getDisplayName();
		$data[AccountManager::PROPERTY_PHONE] = $userAccount[AccountManager::PROPERTY_PHONE]['value'];
		$data[AccountManager::PROPERTY_ADDRESS] = $userAccount[AccountManager::PROPERTY_ADDRESS]['value'];
		$data[AccountManager::PROPERTY_WEBSITE] = $userAccount[AccountManager::PROPERTY_WEBSITE]['value'];
		$data[AccountManager::PROPERTY_TWITTER] = $userAccount[AccountManager::PROPERTY_TWITTER]['value'];
		$data['groups'] = $gids;
		$data['language'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'lang');
		$data['locale'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'locale');

		$backend = $targetUserObject->getBackend();
		$data['backendCapabilities'] = [
			'setDisplayName' => $backend instanceof ISetDisplayNameBackend || $backend->implementsActions(Backend::SET_DISPLAYNAME),
			'setPassword' => $backend instanceof ISetPasswordBackend || $backend->implementsActions(Backend::SET_PASSWORD),
		];

		return $data;
    }


    /**
	 * Get the groups a user is a subadmin of
	 *
	 * @param string $userId
	 * @return array
	 * @throws OCSException
	 */
	protected function getUserSubAdminGroupsData(string $userId): array {
		$user = $this->userManager->get($userId);
		// Check if the user exists
		if($user === null) {
			throw new OCSNotFoundException('User does not exist');
		}

		// Get the subadmin groups
		$subAdminGroups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($user);
		$groups = [];
		foreach ($subAdminGroups as $key => $group) {
			$groups[] = $group->getGID();
		}

		return $groups;
	}

	/**
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $userId
	 * @return array
	 * @throws \OCP\Files\NotFoundException
	 */
	public function fillStorageInfo(string $userId = ''): array {
		$data = [];
		try {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($userId);
			$storage = OC_Helper::getStorageInfo('/');
			$data = [
				'free' => $storage['free'],
				'used' => $storage['used'],
				'total' => $storage['total'],
				'relative' => $storage['relative'],
				'quota' => $storage['quota'],
			];
		} catch (NotFoundException $ex) {
			// User fs is not setup yet
			$user = $this->userManager->get($userId);
			if ($user === null) {
				throw new OCSException('User does not exist', 101);
			}
			$quota = $user->getQuota();
			if ($quota !== 'none') {
				$quota = OC_Helper::computerFileSize($quota);
			}
			$data = [
				'quota' => $quota !== false ? $quota : 'none',
				'used' => 0
			];
		}
		return $data;
	}

	/**
	 * ip/nextcloud/index.php/apps/virwork_api/users?operation_code=virwork_cloudstorage_account
	 * returns a list of users
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getUsers(string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		
       
        $users = [];


		
		
		$users = $this->userManager->search('', null, 0);
		
		$users = array_keys($users);

		return new DataResponse(['users' => $users, 'result' => true]);
	}

       
	/**
	 * returns a list of groups
	 *
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getGroups(string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		$groups = $this->groupManager->search('', null, 0);
		$groups = array_map(function($group) {
			/** @var IGroup $group */
			return $group->getGID();
		}, $groups);
		

		return new DataResponse(['groups' => $groups, 'result' => true]);
	}

   
	/**
	 * create user or update user inforamtion
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $userid
	 * @param string $password
	 * @param string $displayname
	 * @param string $enabled
	 * @param array $groups
	 * @param string $virwork_login_token
	 * @param string $quota
	 * @param string $upload
	 * @param string $download
	 * @param string $delete
	 * @param string $local_share
	 * @param string $public_share
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addUser(string $userid = '',
							string $password = '',
							string $displayname = '',
							string $enabled = '',
							array $groups = [],
							string $virwork_login_token = '',
							string $quota = '',
					   		string $upload   = '',
					   		string $download = '',
					   		string $delete   = '',
					   		string $local_share = '',
					   		string $public_share = '',
							string $operation_code = ''): DataResponse {

		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		if ($userid == '') {
			$this->logger->error('Failed addUser attempt: username is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Failed addUser attempt: username is null.',
				 'error_code' => 102
			   ]);
		}

		if (!is_array($groups) || $groups == []) {
			$this->logger->error('Failed addUser attempt: groups is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Failed addUser attempt: groups is null.',
				 'error_code' => 102
			   ]);
		}

		if (is_null($virwork_login_token)) {
			$this->logger->error('Failed addUser attempt: token is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Failed addUser attempt: token is null.',
				 'error_code' => 102
			   ]);
		}	

		
		 $lang = 'zh_CN';
		 $locale = 'zh_Hans';


		if (is_array($groups) && $groups !== []) {
			foreach ($groups as $group) {
				if (!empty($group))
					if (!$this->groupManager->groupExists($group)) {
						// add this new group
						$this->addGroup($group, $operation_code);
					}
			}
		} else {
			$this->logger->error('no group specified.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'no group specified.',
				 'error_code' => 106
			   ]);
		}
			
		// enable/disable the user now
		$isEnanbled = ($enabled !== 'false' || $enabled !== '0' && $enabled !== false);

		$isEnanbledValues = $isEnanbled ? 'true' : 'false';

		if ($userid === 'admin') {
			$isEnanbled = 'true';
		}	

		if ($this->userManager->userExists($userid)) {

			$targetUser = $this->userManager->get($userid);

			if ($password !== '') {

				// TODO: Add all the insane error handling
				/* @var $loginResult IUser */
				$loginResult = $this->userManager->checkPasswordNoLogging($userid, $password);
				if ($loginResult === false) {
					$users = $this->userManager->getByEmail($userid);
					// we only allow login by email if unique
					if (count($users) === 1) {
						$previousUser = $userid;
						$username = $users[0]->getUID();
						if($username !== $previousUser) {
							$loginResult = $this->userManager->checkPassword($userid, $password);
						}
					}
				}

				if ($loginResult === false) {
					// update passwd
					$targetUser->setPassword($password);
				}


			}

			if ($displayname !== '') {
				$targetUser->setDisplayName($displayname);
			}
			$targetUser->setEnabled($isEnanbled);
        
        	$this->config->setUserValue($targetUser->getUID(), 'core', 'virwork_login_token', $virwork_login_token);
		
			$this->config->setUserValue($targetUser->getUID(), 'core', 'enabled', $isEnanbledValues);
			$this->config->setUserValue($targetUser->getUID(), 'core', 'lang', $lang);
			$this->config->setUserValue($targetUser->getUID(), 'core', 'locale', $locale);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'download', $download);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'upload', $upload);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'delete', $delete);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'local_share', $local_share);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'public_share', $public_share);

			if ($quota !== 'none' && $quota !== 'default') {
				if (is_numeric($quota)) {
					$quota = (float) $quota;
				} else {
					$quota = \OCP\Util::computerFileSize($quota);
				}
				$quota = \OCP\Util::humanFileSize($quota);
				$targetUser->setQuota($quota);
			}

			

   			$oldgroups = $this->groupManager->getUserGroupIds($targetUser);     
            $removegroups = array_diff($oldgroups,$groups);   
            $addgroups = array_diff($groups,$oldgroups);       
                        
             if(!empty($removegroups)){     
                foreach ($removegroups as $group) {
                    if(!empty($group)){        
                        $this->groupManager->get($group)->removeUser($targetUser);
                        $this->logger->info('Added userid '.$userId.' to group '.$group, ['app' => 'virwork_api']);
                    }
                }
             }

            if(!empty($addgroups)){     
                foreach ($addgroups as $group) {
                    if(!empty($group)){        
                        $this->groupManager->get($group)->addUser($targetUser);
                        $this->logger->info('Added userid '.$userId.' to group '.$group, ['app' => 'virwork_api']);
                                        }
                    }
            }



			return new DataResponse([
				'result' => true,
				'message' => 'update User ["'.$userid.'"] Successful.'
			   ]);
		}



 
		if ($password === '') {
			$password = $this->secureRandom->generate(10);
		}

		$client_token = $this->secureRandom->generate(32);

		try {

		    $targetUser = $this->userManager->createUser($userid, $password);
		    // init user
			$targetUser->updateLastLoginTimestamp();       
            $userFolder = \OC::$server->getUserFolder($userid);
            \OC_Util::copySkeleton($userid, $userFolder);
                                

			$this->logger->info('Successful addUser call with userid: ' . $userid, ['app' => 'virwork_api']);

			

			foreach ($groups as $group) {
				$this->groupManager->get($group)->addUser($targetUser);
				$this->logger->info('Added userid ' . $userid . ' to group ' . $group, ['app' => 'virwork_api']);
			}
			

			if ($displayname !== '') {
				
				$targetUser->setDisplayName($displayname);

			}
            
			$targetUser->setEnabled($isEnanbled);
			
			$this->config->setUserValue($targetUser->getUID(), 'core', 'enabled', $isEnanbledValues);
	
			$this->config->setUserValue($targetUser->getUID(), 'core', 'virwork_login_token', $virwork_login_token);
		
		
			$this->config->setUserValue($targetUser->getUID(), 'core', 'lang', $lang);
			$this->config->setUserValue($targetUser->getUID(), 'core', 'locale', $locale);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'download', $download);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'upload', $upload);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'delete', $delete);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'local_share', $local_share);
			$this->config->setUserValue($targetUser->getUID(), 'files', 'public_share', $public_share);



			if ($quota !== 'none' && $quota !== 'default') {
				if (is_numeric($quota)) {
					$quota = (float) $quota;
				} else {
					$quota = \OCP\Util::computerFileSize($quota);
				}
				$quota = \OCP\Util::humanFileSize($quota);

				$targetUser->setQuota($quota);
			}
 
			return new DataResponse([
				'result' => true,
				'message' => 'addU User ["'.$userid.'"] Successful.'
			   ]);

		} catch (HintException $e ) {
			$this->logger->logException($e, [
				'message' => 'Failed addUser attempt with hint exception.',
				'level' => ILogger::WARN,
				'app' => 'virwork_api',
			]);
			throw new OCSException($e->getHint(), 107);
		} catch (\Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Failed addUser attempt with exception.',
				'level' => ILogger::ERROR,
				'app' => 'virwork_api',
			]);
			throw new OCSException('Bad request', 101);
		}
	}



	/**
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function disableUser(string $userId): DataResponse {
		return $this->setEnabled($userId, false);
	}

	/**
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function enableUser(string $userId): DataResponse {
		return $this->setEnabled($userId, true);
	}

	/**
	 * @param string $userId
	 * @param bool $value
	 * @return DataResponse
	 * @throws OCSException
	 */
	private function setEnabled(string $userId, bool $value): DataResponse {
		
		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null || $targetUser->getUID() === 'admin') {
			throw new OCSException('', 101);
		}

		// enable/disable the user now
		$targetUser->setEnabled($value);
		return new DataResponse();
	}

	

	/**
	 * returns a list of user preferences details with username
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $username
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getUserPreferencesValues(string $username, string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		if ($username == '') {
			$this->logger->error('Failed addUser attempt: username is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Failed addUser attempt: username is null.',
				 'error_code' => 102
			   ]);
		}

		if ($this->userManager->userExists($username)) {

			$upload = $this->config->getUserValue($username, 'files', 'upload', false);
			$download = $this->config->getUserValue($username, 'files', 'download', false);
			$delete = $this->config->getUserValue($username, 'files', 'delete', false);
			$local_share = $this->config->getUserValue($username, 'files', 'local_share', false);
			$public_share = $this->config->getUserValue($username, 'files', 'public_share', false);

			$quota = $this->config->getUserValue($username, 'files', 'quota', '0 B');
			return new DataResponse([
				'upload' => $upload, 
				'download' => $download, 
				'delete' => $delete, 
				'local_share' => $local_share,
				'public_share' => $public_share, 
				'quota' => $quota, 
				'result' => true]);
		}

		return new DataResponse(['message' => 'the user not exist', 'result' => false]);
	}


	
	/**
	 * returns a list of user preferences details with username
	 *
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $username
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getUserPreferencesValuesByUserNameAndFileId(string $username,string $fileid, string $operation_code = ''): DataResponse {
	    if ($operation_code != self::OPERATION_CODE) {
	        $this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
	        return new DataResponse([
	            'result' => false,
	            'message' => 'Not operation permission.',
	            'error_code' => 403
	        ]);
	    }

	    if ($username == '') {
	        $this->logger->error('Failed addUser attempt: username is null.', ['app' => 'virwork_api']);
	        return new DataResponse([
	            'result' => false,
	            'message' => 'Failed addUser attempt: username is null.',
	            'error_code' => 102
	        ]);
	    }

	    if ($fileid == '') {
	        $this->logger->error('Failed addUser attempt: fileid is null.', ['app' => 'virwork_api']);
	        return new DataResponse([
	            'result' => false,
	            'message' => 'Failed addUser attempt: username is null.',
	            'error_code' => 102
	        ]);
	    }

	    if ($this->userManager->userExists($username)) {

	        $isreadfile = $this->config->getUserValue($username, 'readfile', $fileid);

	        return new DataResponse([
	            'isreadfile' => $isreadfile,
	        ]);
	    }

	    return new DataResponse(['message' => 'the user not exist', 'result' => false]);
	}

	/**
	 *
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $username
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function deleteUserValue(string $userId, string $appName, string $key, string $operation_code = ''): DataResponse {
	    $this->config->deleteUserValue($userId, $appName, $key);
	}



	/**
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * edit users
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editUser(string $userId, string $key, string $value): DataResponse {

		$targetUser = $this->userManager->get($userId);
		if ($targetUser === null) {
			
			return new DataResponse([
				'result' => false,
				'message' => 'not found user information.'
			   ]);
		}

		if ($key == 'display') {
			$targetUser->setDisplayName($value);
		} else if ($key == 'quota') {
			    $quota = $value;
				if ($quota !== 'none' && $quota !== 'default') {
					if (is_numeric($quota)) {
						$quota = (float) $quota;
					} else {
						$quota = \OCP\Util::computerFileSize($quota);
					}
					if ($quota === false) {
						throw new OCSException('Invalid quota value '.$value, 103);
					}
					if ($quota === -1) {
						$quota = 'none';
					} else {
						$quota = \OCP\Util::humanFileSize($quota);
					}
				}
				$targetUser->setQuota($quota);
		} else if ($key == 'lang') {
			$this->config->setUserValue($targetUser->getUID(), 'core', 'lang', $value);
		} else if ($key == 'locale') {
			$this->config->setUserValue($targetUser->getUID(), 'core', 'locale', $value);
		} else if ($key == 'download') {
			$this->config->setUserValue($targetUser->getUID(), 'files', 'download', $value);
		} else if ($key == 'upload') {
			$this->config->setUserValue($targetUser->getUID(), 'files', 'upload', $value);
		} else if ($key == 'delete') {
			$this->config->setUserValue($targetUser->getUID(), 'files', 'delete', $value);
		} else if ($key == 'local_share') {
			$this->config->setUserValue($targetUser->getUID(), 'files', 'local_share', $value);
		} else if ($key == 'public_share') {
			$this->config->setUserValue($targetUser->getUID(), 'files', 'public_share', $value);
		} 

		

		 
		return new DataResponse([
				'result' => true,
				'message' => 'edit user inforamtion Successful.'
			   ]);
	}


    /**
	 * returns a list of groups details with ids and displaynames
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getGroupsDetails(string $search = '', int $limit = null, 
		int $offset = 0,
		string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		$groups = $this->groupManager->search($search, $limit, $offset);
		$groups = array_map(function($group) {
			/** @var IGroup $group */
			return [
				'id' => $group->getGID(),
				'displayname' => $group->getDisplayName(),
				'usercount' => $group->count(),
				'disabled' => $group->countDisabled(),
				'canAdd' => $group->canAddUser(),
				'canRemove' => $group->canRemoveUser(),
			];
		}, $groups);

		return new DataResponse(['groups_details' => $groups, 'result' => true]);
	}

	/**
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $groupId
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException	
	 *
	 * @deprecated 14 Use getGroupUsers
	 */
	public function getGroup(string $groupId, string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		if ($groupId == '') {
			$this->logger->error('group index is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'group index is null.',
				 'error_code' => 102
			   ]);
		}
		return $this->getGroupUsers($groupId, $operation_code);
	}



	/**
	 * creates a new group
	 *
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $groupid
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addGroup(string $groupid = '', string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		// Validate name
		if(empty($groupid)) {
			$this->logger->error('Group name not supplied.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Invalid group name.',
				 'error_code' => 101
			   ]);
		}
		// Check if it exists
		if($this->groupManager->groupExists($groupid)){
			return new DataResponse([
				'result' => true,
				'message' => 'group exists.'
			   ]);
		}
		
		$this->groupManager->createGroup($groupid);

		return new DataResponse([
				'result' => true,
				'message' => 'add group Successful.'
			   ]);
	}

    /**
	 * sync new group
	 *
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param array $groups
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addGroups(array $groups = [], string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		// Validate name
		if($groups == []) {
			$this->logger->error('Group size is 0.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Group size is 0.',
				 'error_code' => 101
			   ]);
		}

		$sysgroups = $this->groupManager->search('', null, 0);
		$sysgroups = array_map(function($group) {
			/** @var IGroup $group */
			return $group->getGID();
		}, $sysgroups);

		$delgroups = array_diff($sysgroups, $groups);

		foreach ($delgroups as $delgroup) { 
			// don't remove system admin group
            if($delgroup != 'admin'){        
               $this->groupManager->get($delgroup)->delete();
            }
        }


        if (is_array($groups)) {

        	foreach ($groups as $group) {

				if (!empty($group)) {
					// Check if it exists
					if(!$this->groupManager->groupExists($group)){
						$this->groupManager->createGroup($group);
					} 
				}			
			
			}

        }

		
		
		
		return new DataResponse([
				'result' => true,
				'message' => $_group.' add groups Successful. '.$sysgroupsMap[$_group]
		]);
		
	}



	/**
	 * returns an array of users in the specified group
	 *
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $groupId
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getGroupUsers(string $groupId = '', string $operation_code = ''): DataResponse {
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		if ($groupId == '') {
			$this->logger->error('group index is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'group index is null.',
				 'error_code' => 102
			   ]);
		}
 
		// Check the group exists
		$group = $this->groupManager->get($groupId);
		if ($group !== null) {
			$users = $this->groupManager->get($groupId)->getUsers();
			$users =  array_map(function($user) {
				/** @var IUser $user */
				return $user->getUID();
			}, $users);
			$users = array_values($users);
			return new DataResponse(['users' => $users]);
		}  

		$this->logger->error('group not exist.', ['app' => 'virwork_api']);
		return new DataResponse([
				'result' => false,
				'message' => 'group not exist.',
				 'error_code' => 404
		]);
	}


	/**
	 * returns an array of users details in the specified group
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $groupId
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getGroupUsersDetails(string $groupId = '',
	    string $operation_code = ''): DataResponse {

		 if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}

		if ($groupId == '') {
			$this->logger->error('group index is null.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'group index is null.',
				 'error_code' => 102
			   ]);
		}

		// Check the group exists
		$group = $this->groupManager->get($groupId);
		if ($group == null) {
			$this->logger->error('The requested group could not be found.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'The requested group could not be found.',
				 'error_code' => 102
			   ]);
		}

		$users = $group->searchUsers('', null, 0);

			// Extract required number
			$usersDetails = [];
			foreach ($users as $user) {
				try {
					/** @var IUser $user */
					$userId = (string)$user->getUID();
					$userData = $this->getUserData($userId);
					// Do not insert empty entry
					if (!empty($userData)) {
						$usersDetails[$userId] = $userData;
					} else {
						// Logged user does not have permissions to see this user
						// only showing its id
						$usersDetails[$userId] = ['id' => $userId];
					}
				} catch(OCSNotFoundException $e) {
					// continue if a users ceased to exist.
				}
			}
			return new DataResponse(['users' => $usersDetails]);
	}

	/**
	 *
	 * 将用户添加到组
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 */
	public function addToGroup(array $users= [], string $groupId = '',string $operation_code = ''): DataResponse {
	    if ($groupId === '') {
	        throw new OCSException('', 101);
	    }

	    $group = $this->groupManager->get($groupId);

	    foreach ($users as $userId){
	        $targetUser = $this->userManager->get($userId);
	        if ($group === null) {
	            throw new OCSException('', 102);
	        }
	        if ($targetUser === null) {
	            throw new OCSException('', 103);
	        }

	        // Add user to group
	        $group->addUser($targetUser);
	    }

	    return new DataResponse();
	}



}
