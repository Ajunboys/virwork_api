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


class VirworkLoginController extends Controller {

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
	 * ip/nextcloud/index.php/apps/virwork_api/virwork_roles
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

    
}
