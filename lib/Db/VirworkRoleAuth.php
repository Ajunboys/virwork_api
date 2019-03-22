<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getRole()
 * @method void setRole(string $role)
 * @method string getVvworkRoleId()
 * @method void setVvworkRoleId(string $vvworkRoleId)
 * @method string getVvworkGroupId()
 * @method void setVvworkGroupId(string $vvworkGroupId)
 */
class VirworkRoleAuth extends Entity implements JsonSerializable {
	/** @var string */
	protected $role;
	/** @var integer */
	protected $vvworkRoleId;
	/** @var integer */
	protected $vvworkGroupId;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('role', 'string');
		$this->addType('vvworkRoleId', 'integer');
		$this->addType('vvworkGroupId', 'integer');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'vvworkRoleId' => $this->vvworkRoleId,
            'vvworkGroupId' => $this->vvworkGroupId
        ];
    }


}
