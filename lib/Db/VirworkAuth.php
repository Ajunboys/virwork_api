<?php
namespace OCA\Virwork_API\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUsername()
 * @method void setUsername(string $username)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getClientToken()
 * @method void setClientToken(string $token)
 */
class VirworkAuth extends Entity implements JsonSerializable {
	/** @var string */
	protected $username;
	/** @var string */
	protected $password;
	/** @var string */
	protected $clientToken;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('username', 'string');
		$this->addType('password', 'string');
		$this->addType('clientToken', 'string');
	}

	 public function jsonSerialize() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'clientToken' => $this->clientToken
        ];
    }


}
