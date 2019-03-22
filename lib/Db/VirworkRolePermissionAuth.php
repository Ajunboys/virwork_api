<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getRole()
 * @method void setRole(string $role)
 * @method integer getVvworkRoleId()
 * @method void setVvworkRoleId(integer $vvworkRoleId)
 * @method integer getVvworkGroupId()
 * @method void setVvworkGroupId(integer $vvworkGroupId)
 */
class VirworkRolePermissionAuth extends Entity implements JsonSerializable {
	/** @var string */
	protected $role;
	/** @var integer */
	protected $vvworkRoleId;
	/** @var integer */
	protected $vvworkGroupId;

	/** @var string */
	protected parameterName;
	/** @var string */
	protected parameterValue;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('role', 'string');
		$this->addType('vvworkRoleId', 'integer');
		$this->addType('vvworkGroupId', 'integer');

		$this->addType('parameterName', 'string');
		$this->addType('parameterValue', 'string');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'vvworkRoleId' => $this->vvworkRoleId,
            'vvworkGroupId' => $this->vvworkGroupId,
            'parameterName' => $this->parameterName,
            'parameterValue' => $this->parameterValue
        ];
    }


}
