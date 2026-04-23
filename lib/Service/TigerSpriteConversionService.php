<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Service;

use OCA\Tigersprite\AppInfo\Application;
use OCP\Files\File;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class TigerSpriteConversionService {
	public function __construct(
		private ConfigService $configService,
		private TempStorageService $tempStorageService,
		private CommandRunner $commandRunner,
		private LoggerInterface $logger,
		private IL10N $l10n,
	) {
	}

	/**
	 * @return array{pdfPath: string, pdfName: string, generator: string, workDir: string}
	 */
	public function convertFileToPdf(File $file): array {
		if (!$this->configService->isEnabled()) {
			throw new \RuntimeException($this->l10n->t('TigerSprite is disabled by the administrator.'));
		}

		$workDir = $this->tempStorageService->createWorkDirectory();
		$sourceName = $this->sanitizeBaseName($file->getName());
		$inputPath = $workDir . DIRECTORY_SEPARATOR . $sourceName . '.md';
		$pdfName = pathinfo($file->getName(), PATHINFO_FILENAME) . '.pdf';
		$pdfPath = $workDir . DIRECTORY_SEPARATOR . $pdfName;

		file_put_contents($inputPath, $file->getContent());

		$selectedGenerator = $this->configService->getGenerator();
		$candidates = $selectedGenerator === ConfigService::GENERATOR_AUTO
			? [ConfigService::GENERATOR_WKHTMLTOPDF, ConfigService::GENERATOR_DOMPDF]
			: [$selectedGenerator];

		$errors = [];
		foreach ($candidates as $generator) {
			try {
				$this->runConvertCommand($inputPath, $pdfPath, $generator);
				if (!file_exists($pdfPath)) {
					throw new \RuntimeException('TigerSprite did not create the expected PDF output file.');
				}
				return [
					'pdfPath' => $pdfPath,
					'pdfName' => $pdfName,
					'generator' => $generator,
					'workDir' => $workDir,
				];
			} catch (\Throwable $e) {
				$errors[] = sprintf('%s: %s', $generator, $e->getMessage());
				@unlink($pdfPath);
			}
		}

		$this->logger->error('TigerSprite conversion failed', [
			'app' => Application::APP_ID,
			'fileId' => $file->getId(),
			'fileName' => $file->getName(),
			'errors' => $errors,
		]);
		throw new \RuntimeException($this->l10n->t('Failed to convert file.'));
	}

	public function cleanupWorkDirectory(string $workDir): void {
		$this->tempStorageService->deletePath($workDir);
	}

	private function runConvertCommand(string $inputPath, string $outputPath, string $generator): void {
		$phpPath = trim($this->configService->getPhpPath());
		$cliPath = trim($this->configService->getCliPath());

		if ($cliPath === '') {
			throw new \RuntimeException($this->l10n->t('TigerSprite CLI path is not configured.'));
		}

		$parts = [];
		if ($phpPath !== '') {
			$parts[] = $phpPath;
		}
		$parts[] = $cliPath;
		$parts[] = 'convert';
		$parts[] = '--input=' . $inputPath;
		$parts[] = '--output=' . $outputPath;
		$parts[] = '--generator=' . $generator;

		$result = $this->commandRunner->run($parts, dirname($cliPath));
		if ($this->configService->isDebugLoggingEnabled()) {
			$this->logger->debug('TigerSprite CLI run', [
				'app' => Application::APP_ID,
				'command' => $parts,
				'exitCode' => $result['exitCode'],
				'stdout' => $result['stdout'],
				'stderr' => $result['stderr'],
			]);
		}

		if ($result['exitCode'] !== 0) {
			$message = trim($result['stderr']) !== '' ? trim($result['stderr']) : trim($result['stdout']);
			throw new \RuntimeException($message !== '' ? $message : 'TigerSprite CLI exited with a non-zero status.');
		}
	}

	private function sanitizeBaseName(string $name): string {
		$base = pathinfo($name, PATHINFO_FILENAME);
		$base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?: 'document';
		return trim($base, '._-') !== '' ? trim($base, '._-') : 'document';
	}
}
