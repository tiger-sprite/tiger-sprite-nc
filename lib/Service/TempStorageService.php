<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Service;

use OCA\Tigersprite\AppInfo\Application;
use OCP\ITempManager;

class TempStorageService {
	private const ROOT_DIR = 'tigersprite';

	public function __construct(
		private ITempManager $tempManager,
	) {
	}

	public function getRootPath(): string {
		$root = rtrim($this->tempManager->getTempBaseDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::ROOT_DIR;
		if (!is_dir($root)) {
			mkdir($root, 0777, true);
		}
		return $root;
	}

	public function createWorkDirectory(): string {
		$path = $this->getRootPath() . DIRECTORY_SEPARATOR . 'job_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
		if (!mkdir($path, 0777, true) && !is_dir($path)) {
			throw new \RuntimeException('Failed to create TigerSprite working directory.');
		}
		return $path;
	}

	public function cleanupExpired(int $retentionDays): int {
		$deleted = 0;
		$threshold = time() - ($retentionDays * 86400);
		foreach (glob($this->getRootPath() . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
			if (filemtime($path) !== false && filemtime($path) < $threshold) {
				$this->deletePath($path);
				$deleted++;
			}
		}
		return $deleted;
	}

	public function deletePath(string $path): void {
		if (!file_exists($path)) {
			return;
		}
		if (is_file($path) || is_link($path)) {
			@unlink($path);
			return;
		}
		foreach (scandir($path) ?: [] as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$this->deletePath($path . DIRECTORY_SEPARATOR . $item);
		}
		@rmdir($path);
	}
}
