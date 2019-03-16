<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getGroup()
 * @method void setGroup(string $group)
 * @method string getQuota()
 * @method void setQuota(string $quota)
 * @method int getUpload()
 * @method void setUpload(int $upload)
 * @method int getDownload()
 * @method void setDownload(int $download)
 * @method int getDelete()
 * @method void setDelete(int $delete)
 * @method int getLocalShare()
 * @method void setLocalShare(int $localShare)
 * @method int getPublicShare()
 * @method void setPublicShare(int $publicShare)
 */
class VirworkAuthGroupAccess extends Entity  implements JsonSerializable {
	/** @var string */
	protected $group;
	/** @var string */
	protected $quota;
	/** @var int */
	protected $upload;
	/** @var int */
	protected $download;
	/** @var int */
	protected $delete;
	/** @var int */
	protected $localShare;
	/** @var int */
	protected $publicShare;

	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('group', 'string');
		$this->addType('quota', 'string');		
		$this->addType('upload', 'int');
		$this->addType('download', 'int');
		$this->addType('delete', 'int');
		$this->addType('local_share', 'int');
		$this->addType('public_share', 'int');
	}


	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'group' => $this->group,
            'quota' => $this->quota,
            'upload' => $this->upload,
            'download' => $this->download,
            'delete' => $this->delete,
            'localShare' => $this->localShare,
            'publicShare' => $this->publicShare
        ];
    }
}
