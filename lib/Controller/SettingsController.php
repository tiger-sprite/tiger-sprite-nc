<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Controller;

use OCA\Tigersprite\AppInfo\Application;
use OCA\Tigersprite\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ConfigService $configService,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	public function save(): RedirectResponse {
		$this->configService->setEnabled($this->request->getParam('enabled') === '1');
		$this->configService->setPhpPath((string)$this->request->getParam('php_path', 'php'));
		$this->configService->setCliPath((string)$this->request->getParam('cli_path', ''));
		$this->configService->setGenerator((string)$this->request->getParam('generator', ConfigService::GENERATOR_AUTO));
		$this->configService->setOutputBehavior((string)$this->request->getParam('output_behavior', ConfigService::OUTPUT_DOWNLOAD));
		$this->configService->setRetentionDays((int)$this->request->getParam('retention_days', 1));
		$this->configService->setDebugLoggingEnabled($this->request->getParam('debug_logging') === '1');

		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'tigersprite'])
		);
	}
}
