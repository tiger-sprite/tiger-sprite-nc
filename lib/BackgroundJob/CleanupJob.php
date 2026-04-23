<?php

declare(strict_types=1);

namespace OCA\Tigersprite\BackgroundJob;

use OCA\Tigersprite\Service\ConfigService;
use OCA\Tigersprite\Service\TempStorageService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class CleanupJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private TempStorageService $tempStorageService,
		private ConfigService $configService,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument): void {
		$deleted = $this->tempStorageService->cleanupExpired($this->configService->getRetentionDays());
		if ($this->configService->isDebugLoggingEnabled()) {
			$this->logger->debug('TigerSprite cleanup completed', [
				'deleted' => $deleted,
			]);
		}
	}
}
