<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Listeners;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Tigersprite\AppInfo\Application;
use OCA\Tigersprite\Service\ConfigService;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class FilesListener implements IEventListener {
	public function __construct(
		private ConfigService $configService,
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		$this->initialState->provideLazyInitialState('settings', function (): array {
			return $this->configService->getFrontendState();
		});
		Util::addScript(Application::APP_ID, 'tigersprite-main');
	}
}
