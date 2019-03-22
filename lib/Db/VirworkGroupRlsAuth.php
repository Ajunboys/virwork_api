<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method integer getVvworkGroupId()
 * @method void setVvworkGroupId(integer $vvworkGroupId)
 * @method integer getParentVvworkGroupId()
 * @method void setParentVvworkGroupId(integer $parentVvworkGroupId)
 */
class VirworkGroupRlsAuth extends Entity implements JsonSerializable {
	
	/** @var integer */
	protected $vvworkGroupId;
	/** @var integer */
	protected $parentVvworkGroupId;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('vvworkGroupId', 'integer');
		$this->addType('parentVvworkGroupId', 'integer');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'vvworkGroupId' => $this->vvworkGroupId,
            'parentVvworkGroupId' => $this->parentVvworkGroupId
        ];
    }


}
