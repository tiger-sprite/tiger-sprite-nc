<?php

declare(strict_types=1);

namespace OCA\Tigersprite;

use OCA\Tigersprite\AppInfo\Application;
use OCA\Tigersprite\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings {
	public function __construct(
		private ConfigService $configService,
	) {
	}

	public function getForm(): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'tigersprite-admin');
		Util::addScript(Application::APP_ID, 'tigersprite-admin');

		return new TemplateResponse(Application::APP_ID, 'admin', [
			'config' => $this->configService->getAdminFormValues(),
		]);
	}

	public function getSection(): string {
		return 'tigersprite';
	}

	public function getPriority(): int {
		return 50;
	}
}
