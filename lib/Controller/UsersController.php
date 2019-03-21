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


use OCA\Virwork_API\Db\VirworkAuth;
use OCA\Virwork_API\Db\VirworkAuthMapper;
use OCA\Virwork_API\Db\VirworkAuthGroupAccess;
use OCA\Virwork_API\Db\VirworkAuthGroupAccessMapper;

use OCA\Virwork_API\Exceptions\VirworkAuthGroupAccessNotFoundException;
use OCA\Virwork_API\Exceptions\VirworkAuthNotFoundException;

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
	/** @var VirworkAuthMapper */
	protected $virworkAuthMapper;
	/** @var VirworkAuthGroupAccessMapper */
	protected $virworkAuthGroupAccessMapper;

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
	 * @param VirworkAuthMapper $virworkAuthMapper
	 * @param VirworkAuthGroupAccessMapper $virworkAuthGroupAccessMapper
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
		ISecureRandom $secureRandom,
		VirworkAuthMapper $virworkAuthMapper,
		VirworkAuthGroupAccessMapper $virworkAuthGroupAccessMapper){

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
		$this->virworkAuthMapper = $virworkAuthMapper;
		$this->virworkAuthGroupAccessMapper = $virworkAuthGroupAccessMapper;
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
	 * ip/nextcloud/index.php/apps/virwork_api/user_auth?username=&operation_code=virwork_cloudstorage_account
	 * returns a list of users
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $username
	 * @param string $password
	 * @param string $client_token
	 * @param string $operation_code
	 * @return DataResponse
	 */
    public function saveVirworkAuth(string $username, string $password, 
    	string $client_token = '', string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		
        if ($username == null || $username == '') {
        	$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission, request user is null.',
				 'error_code' => 403
			   ]);
        }

        if ($password == null || $password == '') {
        	$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission, request user password is null.',
				 'error_code' => 403
			   ]);
        }

       
		$password_ = base64_encode(base64_encode($password));

		 // new 
		$newAuth = new VirworkAuth();
		$newAuth->setUsername($username);
		$newAuth->setPassword($password_);
		$newAuth->setClientToken($client_token);
		
        
		$auths = $this->virworkAuthMapper->findAll();

		$isExistsUser = false;

		$auths = array_map(function($auth) {
			/** @var VirworkAuth $auth */
			return $auth;
		}, $auths);

		if ($auths != []) {
			

			foreach ($auths as $auth) {
				if ($auth->getUsername() == $username) {
					$isExistsUser = true;
					$newAuth = $auth;
				}
			}

			
		} 


        if ($isExistsUser) {
        	// update	
			$newAuth->setPassword($password_);

			$newAuth = $this->virworkAuthMapper->update($newAuth);

		    return new DataResponse([
				'result' => true,
				'message' => 'update user Successful.'
			   ]);

		} 

		   
		$newAuth = $this->virworkAuthMapper->insert($newAuth);


		return new DataResponse([
					'result' => true,
					'message' => 'add user Successful.'
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
	protected function getUserData(string $userId): array {
		
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
	 * @param string $userId
	 * @return array
	 * @throws \OCP\Files\NotFoundException
	 */
	protected function fillStorageInfo(string $userId): array {
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
	 * ip/nextcloud/index.php/apps/virwork_api/user_auth?username=&operation_code=virwork_cloudstorage_account
	 * returns a list of users
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $username
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getUserAuthInfo(string $username = null, 
		string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		
        if ($username == null || $username == '') {
        	$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission, request user is null.',
				 'error_code' => 403
			   ]);
        }

		$data = $this->virworkAuthMapper->getAuthByUsername($username);
	
		return new DataResponse(['username'=> $data->getUsername(),
         'password' => $this->secureRandom->generate(32).($data->getPassword()),
		 'result' => true]);
	}


	/**
	 * ip/nextcloud/index.php/apps/virwork_api/user_auth?username=&operation_code=virwork_cloudstorage_account
	 * returns a list of users
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param string $operation_code
	 * @return DataResponse
	 */
	public function getUserAuthInfos(
		string $operation_code = ''): DataResponse {
	
		if ($operation_code != self::OPERATION_CODE) {
			$this->logger->error('Not operation permission.', ['app' => 'virwork_api']);
			return new DataResponse([
				'result' => false,
				'message' => 'Not operation permission.',
				 'error_code' => 403
			   ]);
		}
		
		$data = $this->virworkAuthMapper->findAll();
	
		return new DataResponse(['data'=> $data, 'result' => true]);
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
	 * @param string $displayName
	 * @param array $groups
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addUser(string $userid = '',
							string $password = '',
							string $displayName = '',
							array $groups = [],
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

		

		if ($this->userManager->userExists($userid)) {
			
			if ($displayName !== '') {
				$this->editUser($userid, 'display', $displayName);
			}

			return new DataResponse([
				'result' => true,
				'message' => 'update User ["'.$userid.'"] Successful.'
			   ]);
		}


		if ($groups !== []) {
			foreach ($groups as $group) {
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
 
		if ($password === '') {
			$password = $this->secureRandom->generate(10);
		}

		$client_token = $this->secureRandom->generate(32);

		try {
		    $newUser = $this->userManager->createUser($userid, $password);

			$this->logger->info('Successful addUser call with userid: ' . $userid, ['app' => 'virwork_api']);

			

			foreach ($groups as $group) {
				$this->groupManager->get($group)->addUser($newUser);
				$this->logger->info('Added userid ' . $userid . ' to group ' . $group, ['app' => 'virwork_api']);
			}
			

			if ($displayName !== '') {
				$this->editUser($userid, 'display', $displayName);
			}

			//saveVirworkAuth($userid, $password, $client_token, $operation_code);
		 

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
			throw new OCSException('group exists', 102);
		}
		$this->groupManager->createGroup($groupid);
		return new DataResponse();
	}


    /**
	 * creates a new group
	 *
	 * 
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param array $groupid
	 * @param string $operation_code
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addGroups(array $groupid = [], string $operation_code = ''): DataResponse {
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
		foreach ($groups as $group) {
			// Check if it exists
			if(!$this->groupManager->groupExists($group)){
				$this->groupManager->createGroup($group);
			}
			
		}
		
		return new DataResponse();
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


}
