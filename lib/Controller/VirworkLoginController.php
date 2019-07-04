<?php
namespace OCA\Virwork_API\Controller;

use OCP\AppFramework\Controller;

use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;

use OC\Authentication\Token\IToken;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Security\Bruteforce\Throttler;
use OC\User\Session;

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
use OC_App;
use OC_Util;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;

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
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;
use OC\Hooks\PublicEmitter;


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

	/** @var ISession */
	private $session;
	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var Manager */
	private $twoFactorManager;

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
	 * @param ISession $session
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $twoFactorManager,
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
		ISession $session,
		IURLGenerator $urlGenerator,
		Manager $twoFactorManager,
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
		$this->session = $session;
		$this->urlGenerator = $urlGenerator;
		$this->twoFactorManager = $twoFactorManager;
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

    /**
	 * @param string $redirectUrl
	 * @return RedirectResponse
	 */
	private function generateRedirect($redirectUrl) {
		if (!is_null($redirectUrl) && $this->userSession->isLoggedIn()) {
			$location = $this->urlGenerator->getAbsoluteURL(urldecode($redirectUrl));
			// Deny the redirect if the URL contains a @
			// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
			if (strpos($location, '@') === false) {
				return new RedirectResponse($location);
			}
		}
		return new RedirectResponse(OC_Util::getDefaultPageUrl());
	}
    /**
     * @return string
     */
	private function getServerHostAddress() : string{
		$base_dir = __DIR__;
		
		$doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);

		// server protocol
		$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';

		// domain name
		$domain = $_SERVER['SERVER_NAME'];

		// base url
		$base_url = preg_replace("!^${doc_root}!", '', $base_dir);

		// server port
		$port = $_SERVER['SERVER_PORT'];

		$disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";

		// put em all together to get the complete base URL
		$url = "${protocol}://${domain}${disp_port}${base_url}";

		$url = str_replace("/apps/virwork_api/lib/Controller","", $url);

		return $url;
	}

	/**
	 * 发送HTTP请求
	 *
	 * @param string $url 请求地址
	 * @param string $method 请求方式 GET/POST
	 * @param string $refererUrl 请求来源地址
	 * @param array $data 发送数据
	 * @param string $contentType
	 * @param string $timeout
	 * @param string $proxy
	 * @return boolean
	 */
	function send_request($url, $data, $refererUrl = '', $method = 'GET', $contentType = 'application/json', $timeout = 30, $proxy = false) {
	    $ch = null;
	    if('POST' === strtoupper($method)) {
	        $ch = curl_init($url);
	        curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_HEADER,0 );
	        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
	        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	        if ($refererUrl) {
	            curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
	        }
	        if($contentType) {
	            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
	        }
	        if(is_string($data)){
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	        } else {
	            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	        }
	    } else if('GET' === strtoupper($method)) {
	        if(is_string($data)) {
	            $real_url = $url. (strpos($url, '?') === false ? '?' : ''). $data;
	        } else {
	            $real_url = $url. (strpos($url, '?') === false ? '?' : ''). http_build_query($data);
	        }
	 
	        $ch = curl_init($real_url);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	        if ($refererUrl) {
	            curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
	        }
	    } else {
	        $args = func_get_args();
	        return false;
	    }
	 
	    if($proxy) {
	        curl_setopt($ch, CURLOPT_PROXY, $proxy);
	    }
	    $ret = curl_exec($ch);
	    $info = curl_getinfo($ch);
	    $contents = array(
	            'httpInfo' => array(
	                    'send' => $data,
	                    'url' => $url,
	                    'ret' => $ret,
	                    'http' => $info,
	            )
	    );
	 
	    curl_close($ch);
	    return $ret;
	}
 
	/**
	 * 
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $remember
	 * @param string $token
	 * @param string $redirect_url
	 * @param string $auth_url
	 * @return RedirectResponse
	 */
	public function tryGetLogin(string $user = '', string $password = '', string $remember = '', string $token = '', string $redirect_url = '/index.php/apps/files/',string $auth_url = '') {
		
		// $user = 'admin';
		// $password = '123456';

		// $token = 'EWSDSDWQE@#$SDCVZADFSDGXCVSDF=';

		if(!is_string($user)) {
			return;
		}

		if (is_null($token)) {
			throw new OCSException('Not Validator Authentication key!', 101);
		}
		if (is_null($auth_url)) {
			throw new OCSException('Not Validator Authentication Host!', 101);
		}

		$virworkLogined = $this->config->getUserValue($user, 'core', 'virwork_login_token', '') === $token;

		if(!$virworkLogined) {
			//return;
		}

        
		$virwork_auth_url = 'http://127.0.0.1:58887';
		
		$validatorRequestURL = $virwork_auth_url.'/api/system-files/cloudstorage-api/validator/'.$user.'/'.$token;	
			$validatorResult = $this->send_request($validatorRequestURL, '', '',
		 'GET', 'application/json',  30, false);
		
		$this->logger->debug('validatorResult: '. $validatorResult ,
				['app' => 'virwork_api']);
		if(is_null($validatorResult) || $validatorResult != 'true') {
				throw new OCSException('Not Validator Authentication!', 101);
		} 
		 
		if (is_null($password)) {
			return;
		} else {
			$base64_password = substr($password, 12, strlen($password));
			$rel_password = base64_decode($base64_password);
			$password = base64_decode($rel_password);
		}

		//$password = '654123';

		 $remember_login = false;
		 if($remember === 'sleep') $remember_login = true;
		 $timezone = '';
		 $timezone_offset = '';

	
		// If the user is already logged in and the CSRF check does not pass then
		// simply redirect the user to the correct page as required. This is the
		// case when an user has already logged-in, in another tab.
		if(!$this->request->passesCSRFCheck()) {
			//return $this->generateRedirect($redirect_url);
		}

		if ($this->userManager instanceof PublicEmitter) {
			$this->userManager->emit('\OC\User', 'preLogin', array($user, $password));
		}

		$originalUser = $user;

		$userObj = $this->userManager->get($user);

		if ($userObj !== null && $userObj->isEnabled() === false) {
			$this->logger->warning('Login failed: \''. $user . '\' disabled' .
				' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'virwork_api']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_USERDISABLED);
		}

		// TODO: Add all the insane error handling
		/* @var $loginResult IUser */
		$loginResult = $this->userManager->checkPasswordNoLogging($user, $password);

		if ($loginResult === false) {
			$users = $this->userManager->getByEmail($user);
			// we only allow login by email if unique
			if (count($users) === 1) {
				$previousUser = $user;
				$user = $users[0]->getUID();
				if($user !== $previousUser) {
					$loginResult = $this->userManager->checkPassword($user, $password);
				}
			}
		}

		if ($loginResult === false) {
			$this->logger->warning('Login failed: \''. $user .
				'\' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'virwork_api']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_INVALIDPASSWORD);
		}

		// TODO: remove password checks from above and let the user session handle failures
		// requires https://github.com/owncloud/core/pull/24616
		$this->userSession->completeLogin($loginResult, ['loginName' => $user, 'password' => $password]);

		$tokenType = IToken::REMEMBER;
		if ((int)$this->config->getSystemValue('remember_login_cookie_lifetime', 60*60*24*15) === 0) {
			$remember_login = false;
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $user, $password, $tokenType);
		$this->userSession->updateTokens($loginResult->getUID(), $password);

		// User has successfully logged in, now remove the password reset link, when it is available
		$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');

		$this->session->set('last-password-confirm', $loginResult->getLastLogin());

        $this->config->setUserValue($loginResult->getUID(), 'core', 'virwork_login_token', $token); 

		if ($timezone_offset !== '') {
			$this->config->setUserValue($loginResult->getUID(), 'core', 'timezone', $timezone);
			$this->session->set('timezone', $timezone_offset);
		}

		if ($this->twoFactorManager->isTwoFactorAuthenticated($loginResult)) {
			$this->twoFactorManager->prepareTwoFactorLogin($loginResult, $remember_login);

			$providers = $this->twoFactorManager->getProviderSet($loginResult)->getPrimaryProviders();
			if (count($providers) === 1) {
				// Single provider, hence we can redirect to that provider's challenge page directly
				/* @var $provider IProvider */
				$provider = array_pop($providers);
				$url = 'core.TwoFactorChallenge.showChallenge';
				$urlParams = [
					'challengeProviderId' => $provider->getId(),
				];
			} else {
				$url = 'core.TwoFactorChallenge.selectChallenge';
				$urlParams = [];
			}

			if (!is_null($redirect_url)) {
				$urlParams['redirect_url'] = $redirect_url;
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute($url, $urlParams));
		}

		if ($remember_login) {
			$this->userSession->createRememberMeToken($loginResult);
		}

		return $this->generateRedirect($redirect_url);
	}
 
	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $remember
	 * @param string $token
	 * @param string $redirect_url
	 * @return RedirectResponse
	 */
	public function tryPostLogin($user, $password , $remember, $token, $redirect_url = '/index.php/apps/files/') {
		
		// $user = 'admin';
		// $password = '123456';

		// $token = 'EWSDSDWQE@#$SDCVZADFSDGXCVSDF=';

		if(!is_string($user)) {
			return;
		}

		if (is_null($token)) {
			return;
		}

		$virworkLogined = $this->config->getUserValue($user, 'core', 'virwork_login_token', '') === $token;

		if(!$virworkLogined) {
			return;
		}

		if (is_null($password)) {
			return;
		} else {
			$base64_password = substr($password, 12, strlen($password));
			$rel_password = base64_decode($base64_password);
			$password = base64_decode($rel_password);
		}

		//$password = '654123';

		 $remember_login = false;
		 if($remember === 'sleep') $remember_login = true;
		 $timezone = '';
		 $timezone_offset = '';

	
		// If the user is already logged in and the CSRF check does not pass then
		// simply redirect the user to the correct page as required. This is the
		// case when an user has already logged-in, in another tab.
		if(!$this->request->passesCSRFCheck()) {
			//return $this->generateRedirect($redirect_url);
		}

		if ($this->userManager instanceof PublicEmitter) {
			$this->userManager->emit('\OC\User', 'preLogin', array($user, $password));
		}

		$originalUser = $user;

		$userObj = $this->userManager->get($user);

		if ($userObj !== null && $userObj->isEnabled() === false) {
			$this->logger->warning('Login failed: \''. $user . '\' disabled' .
				' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'core']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_USERDISABLED);
		}

		// TODO: Add all the insane error handling
		/* @var $loginResult IUser */
		$loginResult = $this->userManager->checkPasswordNoLogging($user, $password);

		if ($loginResult === false) {
			$users = $this->userManager->getByEmail($user);
			// we only allow login by email if unique
			if (count($users) === 1) {
				$previousUser = $user;
				$user = $users[0]->getUID();
				if($user !== $previousUser) {
					$loginResult = $this->userManager->checkPassword($user, $password);
				}
			}
		}

		if ($loginResult === false) {
			$this->logger->warning('Login failed: \''. $user .
				'\' (Remote IP: \''. $this->request->getRemoteAddress(). '\')',
				['app' => 'core']);
			return $this->createLoginFailedResponse($user, $originalUser,
				$redirect_url, self::LOGIN_MSG_INVALIDPASSWORD);
		}

		// TODO: remove password checks from above and let the user session handle failures
		// requires https://github.com/owncloud/core/pull/24616
		$this->userSession->completeLogin($loginResult, ['loginName' => $user, 'password' => $password]);

		$tokenType = IToken::REMEMBER;
		if ((int)$this->config->getSystemValue('remember_login_cookie_lifetime', 60*60*24*15) === 0) {
			$remember_login = false;
			$tokenType = IToken::DO_NOT_REMEMBER;
		}

		$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $user, $password, $tokenType);
		$this->userSession->updateTokens($loginResult->getUID(), $password);

		// User has successfully logged in, now remove the password reset link, when it is available
		$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');

		$this->session->set('last-password-confirm', $loginResult->getLastLogin());

        $this->config->setUserValue($loginResult->getUID(), 'core', 'virwork_login_token', $token); 

		if ($timezone_offset !== '') {
			$this->config->setUserValue($loginResult->getUID(), 'core', 'timezone', $timezone);
			$this->session->set('timezone', $timezone_offset);
		}

		if ($this->twoFactorManager->isTwoFactorAuthenticated($loginResult)) {
			$this->twoFactorManager->prepareTwoFactorLogin($loginResult, $remember_login);

			$providers = $this->twoFactorManager->getProviderSet($loginResult)->getPrimaryProviders();
			if (count($providers) === 1) {
				// Single provider, hence we can redirect to that provider's challenge page directly
				/* @var $provider IProvider */
				$provider = array_pop($providers);
				$url = 'core.TwoFactorChallenge.showChallenge';
				$urlParams = [
					'challengeProviderId' => $provider->getId(),
				];
			} else {
				$url = 'core.TwoFactorChallenge.selectChallenge';
				$urlParams = [];
			}

			if (!is_null($redirect_url)) {
				$urlParams['redirect_url'] = $redirect_url;
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute($url, $urlParams));
		}

		if ($remember_login) {
			$this->userSession->createRememberMeToken($loginResult);
		}

		return $this->generateRedirect($redirect_url);
	}

    
	/**
	 *
     * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $username
	 *
	 * @return DataResponse
	 */
	public function logout(string $username = '') : DataResponse {

        if (is_null($username)) {
        	return new DataResponse([
				'result' => true,
				'message' => 'user is null.',
				 'error_code' => 403
			   ]);
        }

        $sessionuser = $this->userSession->getUser();
        if (!is_null($sessionuser)) {
        	$sessionuser = $sessionuser->getUID();
        }

		$loginToken = $this->request->getCookie('nc_token');
		if (!is_null($loginToken)) {
			$this->config->deleteUserValue($username, 'login_token', $loginToken);
		}

        $this->config->setUserValue($username, 'core', 'virwork_login_token', $this->secureRandom->generate(32)); 

		$this->userSession->logout();

		return new DataResponse([
				'result' => true
			   ]);
	}
}

