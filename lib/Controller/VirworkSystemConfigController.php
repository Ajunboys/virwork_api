<?php
namespace OCA\Virwork_API\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Controller;


use OC\Template\SCSSCacher;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;

use OCA\Theming\Util;
use OCP\ITempManager;

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


use OCP\Files\File;
use OCP\Files\IAppData;

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


class VirworkSystemConfigController extends Controller {
    /** @var ThemingDefaults */
	private $themingDefaults;
	/** @var SCSSCacher */
	private $scssCacher;

	/** @var IAppData */
	private $appData;
	/** @var ImageManager */
	private $imageManager;

	/** @var ITempManager */
	private $tempManager;
	/** @var Util */
	private $util;

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
	 * @param ThemingDefaults $themingDefaults
	 * @param SCSSCacher $scssCacher
	 * @param IAppData $appData
	 * @param ImageManager $imageManager	 
	 * @param Util $util
	 * @param ImageManager $imageManager
	 * @param IAppManager $appManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param AccountManager $accountManager
	 * @param ILogger $logger
	 * @param IFactory $l10nFactory
	 * @param NewUserMailHelper $newUserMailHelper
	 * @param FederatedFileSharingFactory $federatedFileSharingFactory
	 * @param ISecureRandom $secureRandom
	 *
	 */
	public function __construct($appName, 
		IRequest $request, 
	    IUserManager $userManager,
		IConfig $config,
		ThemingDefaults $themingDefaults,
		SCSSCacher $scssCacher,
		IAppData $appData,
		Util $util,
		ImageManager $imageManager,
		ITempManager $tempManager,
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

		$this->themingDefaults = $themingDefaults;

		$this->scssCacher = $scssCacher;

		$this->appData = $appData;
		$this->util = $util;

		$this->tempManager = $tempManager;
		$this->imageManager = $imageManager;

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

		$this->virworkUserRoleAuthMapper = $virworkUserRoleAuthMapper;

		$this->virworkRoleAuthMapper = $virworkRoleAuthMapper;
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
	 * ip/nextcloud/index.php/apps/virwork_api/system_config
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
	 * ip/nextcloud/index.php/apps/virwork_api/system_config/android/version
	 * returns android information(version)
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */ 
    public function getAndroidVersion(): DataResponse {
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

		return new DataResponse([
				'result' => true,
				'data' => [
					 'version'=>'20190329',
					 'descriptions'=>'',
					 'path'=>$url.'/download/20190329.apk',
				]
		]);
    }


    /**
	 * ip/nextcloud/index.php/apps/virwork_api/system_config/ios/version
	 * returns ios information(version)
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */ 
    public function getIOSVersion(): DataResponse {
		return new DataResponse([
				'result' => true,
				'data' => [
					 'version'=>'20190329',
					 'descriptions'=>'',
					 'path'=>'https://itunes.apple.com/cn/app/virwork-app/id516447913?mt=8'
				]
		]);
    }



	/**
	 *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $setting
	 * @param string $value
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function updateStylesheet($setting, $value) {
		$value = trim($value);
		switch ($setting) {
			case 'name':
				if (strlen($value) > 250) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given name is too long'
					]);
				}
				break;
			case 'url':
				if (strlen($value) > 500) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given web address is too long'
					]);
				}
				break;
			case 'imprintUrl':
				if (strlen($value) > 500) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given legal notice address is too long'
					]);
				}
				break;
			case 'privacyUrl':
				if (strlen($value) > 500) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given privacy policy address is too long'
					]);
				}
				break;
			case 'slogan':
				if (strlen($value) > 500) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given slogan is too long'
					]);
				}
				break;
			case 'color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					return new DataResponse([
						'result' => false,
						'message' => 'The given color is invalid'
					]);
				}
				break;
		}

		$this->themingDefaults->set($setting, $value);

		// reprocess server scss for preview
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/css-variables.scss', 'core');

		
		return new DataResponse([
				'result' => true,
				'message' => 'update style sheet Successful.'
			   ]);
	}


	/**
     *
	 * @PublicPage 
	 * @NoAdminRequired
	 * @NoCSRFRequired
     *
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function uploadImage(): DataResponse {
		// logo / background
		// new: favicon logo-header
		//
		$key = $this->request->getParam('key');
		$image = $this->request->getUploadedFile('image');
		$error = null;
		$phpFileUploadErrors = [
			UPLOAD_ERR_OK => 'The file was uploaded',
			UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
			UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
			UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
			UPLOAD_ERR_CANT_WRITE => 'Could not write file to disk',
			UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
		];
		if (empty($image)) {
			$error = 'No file uploaded';
		}
		if (!empty($image) && array_key_exists('error', $image) && $image['error'] !== UPLOAD_ERR_OK) {
			$error = $phpFileUploadErrors[$image['error']];
		}

		if ($error !== null) {
			return new DataResponse(
				[
					'result' => false,
					'message' => $error
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$name = '';
		try {
			$folder = $this->appData->getFolder('images');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('images');
		}

		$this->imageManager->delete($key);

		$target = $folder->newFile($key);
		$supportedFormats = $this->getSupportedUploadImageFormats($key);
		$detectedMimeType = mime_content_type($image['tmp_name']);
		if (!in_array($image['type'], $supportedFormats) || !in_array($detectedMimeType, $supportedFormats)) {
			return new DataResponse(
				[
					'result' => false,						
					'message' => 'Unsupported image type',

				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$resizeKeys = ['background'];
		if (in_array($key, $resizeKeys, true)) {
			// Optimize the image since some people may upload images that will be
			// either to big or are not progressive rendering.
			$newImage = @imagecreatefromstring(file_get_contents($image['tmp_name'], 'r'));

			$tmpFile = $this->tempManager->getTemporaryFile();
			$newWidth = imagesx($newImage) < 4096 ? imagesx($newImage) : 4096;
			$newHeight = imagesy($newImage) / (imagesx($newImage) / $newWidth);
			$outputImage = imagescale($newImage, $newWidth, $newHeight);

			imageinterlace($outputImage, 1);
			imagejpeg($outputImage, $tmpFile, 75);
			imagedestroy($outputImage);

			$target->putContent(file_get_contents($tmpFile, 'r'));
		} else {
			$target->putContent(file_get_contents($image['tmp_name'], 'r'));
		}
		$name = $image['name'];

		$this->themingDefaults->set($key.'Mime', $image['type']);

		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/css-variables.scss', 'core');

		return new DataResponse(
			[
				'result' => true,
				'message' => 'upload image('.$name.') Successful.'
			]
		);
	}



	/**
	 * Returns a list of supported mime types for image uploads.
	 * "favicon" images are only allowed to be SVG when imagemagick with SVG support is available.
	 *
	 * @param string $key The image key, e.g. "favicon"
	 * @return array
	 */
	private function getSupportedUploadImageFormats(string $key): array {
		$supportedFormats = ['image/jpeg', 'image/png', 'image/gif',];

		if ($key !== 'favicon' || $this->imageManager->shouldReplaceIcons() === true) {
			$supportedFormats[] = 'image/svg+xml';
			$supportedFormats[] = 'image/svg';
		}

		return $supportedFormats;
	}

    
}

