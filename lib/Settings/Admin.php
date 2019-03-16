<?php
declare(strict_types=1);

namespace OCA\Virwork_API\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function getForm(): TemplateResponse {
		return new TemplateResponse(
			'virwork_api',
			'admin',
			[],
			''
		);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 0;
	}
}
