<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getGroup()
 * @method void setGroup(string $group)
 * @method integer getVvworkGroupId()
 * @method void setVvworkGroupId(integer $vvworkGroupId)
 * @method array getChilds()
 * @method void setVvworkGroupId(array $childs)
 */
class VirworkGroupAuth extends Entity implements JsonSerializable {
	/** @var string */
	protected $group;
	/** @var integer */
	protected $vvworkGroupId;

	protected $childs;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('group', 'string');
		$this->addType('vvworkGroupId', 'integer');
		$this->addType('childs', 'array');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'group' => $this->group,
            'vvworkGroupId' => $this->vvworkGroupId,
            'childs' => $this->childs
        ];
    }


}
