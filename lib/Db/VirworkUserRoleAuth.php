<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method integer getVvworkUserId()
 * @method void setVvworkUserId(integer $vvworkUserId)
 * @method integer getVvworkRoleId()
 * @method void setVvworkRoleId(integer $vvworkRoleId)
 */
class VirworkUserRoleAuth extends Entity implements JsonSerializable {
	
	/** @var string */
	protected $username;
	/** @var integer */
	protected $vvworkUserId;
	/** @var integer */
	protected $vvworkRoleId;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('username', 'string');
		$this->addType('vvworkUserId', 'integer');
		$this->addType('vvworkRoleId', 'integer');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'vvworkUserId' => $this->vvworkUserId,
            'vvworkRoleId' => $this->vvworkRoleId
        ];
    }


}
