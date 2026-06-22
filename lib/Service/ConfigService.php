<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Service;

use OCA\Tigersprite\AppInfo\Application;
use OCP\IConfig;

class ConfigService {
	public const OUTPUT_DOWNLOAD = 'download';
	public const OUTPUT_SAVE = 'save';
	public const GENERATOR_AUTO = 'auto';
	public const GENERATOR_WKHTMLTOPDF = 'wkhtmltopdf';
	public const GENERATOR_DOMPDF = 'dompdf';
	public const RUNTIME_CLI = 'cli';

	private const KEY_ENABLED = 'enabled';
	private const KEY_RUNTIME_MODE = 'runtime_mode';
	private const KEY_PHP_PATH = 'php_path';
	private const KEY_CLI_PATH = 'cli_path';
	private const KEY_GENERATOR = 'generator';
	private const KEY_OUTPUT_BEHAVIOR = 'output_behavior';
	private const KEY_RETENTION_DAYS = 'retention_days';
	private const KEY_DEBUG_LOGGING = 'debug_logging';

	private const DEFAULT_PHP_PATH = 'php';
	private const DEFAULT_CLI_PATH = '';

	public function __construct(
		private IConfig $config,
	) {
	}

	public function isEnabled(): bool {
		return $this->config->getAppValue(Application::APP_ID, self::KEY_ENABLED, 'yes') === 'yes';
	}

	public function setEnabled(bool $enabled): void {
		$this->config->setAppValue(Application::APP_ID, self::KEY_ENABLED, $enabled ? 'yes' : 'no');
	}

	public function getRuntimeMode(): string {
		return self::RUNTIME_CLI;
	}

	public function getPhpPath(): string {
		return $this->config->getAppValue(Application::APP_ID, self::KEY_PHP_PATH, self::DEFAULT_PHP_PATH);
	}

	public function setPhpPath(string $path): void {
		$this->config->setAppValue(Application::APP_ID, self::KEY_PHP_PATH, trim($path) !== '' ? trim($path) : self::DEFAULT_PHP_PATH);
	}

	public function getCliPath(): string {
		return $this->config->getAppValue(Application::APP_ID, self::KEY_CLI_PATH, self::DEFAULT_CLI_PATH);
	}

	public function setCliPath(string $path): void {
		$this->config->setAppValue(Application::APP_ID, self::KEY_CLI_PATH, trim($path));
	}

	public function getGenerator(): string {
		$value = $this->config->getAppValue(Application::APP_ID, self::KEY_GENERATOR, self::GENERATOR_AUTO);
		if (!in_array($value, [self::GENERATOR_AUTO, self::GENERATOR_WKHTMLTOPDF, self::GENERATOR_DOMPDF], true)) {
			return self::GENERATOR_AUTO;
		}
		return $value;
	}

	public function setGenerator(string $generator): void {
		$value = in_array($generator, [self::GENERATOR_AUTO, self::GENERATOR_WKHTMLTOPDF, self::GENERATOR_DOMPDF], true)
			? $generator
			: self::GENERATOR_AUTO;
		$this->config->setAppValue(Application::APP_ID, self::KEY_GENERATOR, $value);
	}

	public function getOutputBehavior(): string {
		$value = $this->config->getAppValue(Application::APP_ID, self::KEY_OUTPUT_BEHAVIOR, self::OUTPUT_DOWNLOAD);
		if (!in_array($value, [self::OUTPUT_DOWNLOAD, self::OUTPUT_SAVE], true)) {
			return self::OUTPUT_DOWNLOAD;
		}
		return $value;
	}

	public function setOutputBehavior(string $behavior): void {
		$value = in_array($behavior, [self::OUTPUT_DOWNLOAD, self::OUTPUT_SAVE], true)
			? $behavior
			: self::OUTPUT_DOWNLOAD;
		$this->config->setAppValue(Application::APP_ID, self::KEY_OUTPUT_BEHAVIOR, $value);
	}

	public function getRetentionDays(): int {
		$value = (int)$this->config->getAppValue(Application::APP_ID, self::KEY_RETENTION_DAYS, '1');
		return max(0, $value);
	}

	public function setRetentionDays(int $days): void {
		$this->config->setAppValue(Application::APP_ID, self::KEY_RETENTION_DAYS, (string)max(0, $days));
	}

	public function isDebugLoggingEnabled(): bool {
		return $this->config->getAppValue(Application::APP_ID, self::KEY_DEBUG_LOGGING, 'no') === 'yes';
	}

	public function setDebugLoggingEnabled(bool $enabled): void {
		$this->config->setAppValue(Application::APP_ID, self::KEY_DEBUG_LOGGING, $enabled ? 'yes' : 'no');
	}

	public function getAdminFormValues(): array {
		return [
			'enabled' => $this->isEnabled(),
			'runtimeMode' => $this->getRuntimeMode(),
			'phpPath' => $this->getPhpPath(),
			'cliPath' => $this->getCliPath(),
			'generator' => $this->getGenerator(),
			'outputBehavior' => $this->getOutputBehavior(),
			'retentionDays' => $this->getRetentionDays(),
			'debugLogging' => $this->isDebugLoggingEnabled(),
		];
	}

	public function getFrontendState(): array {
		return [
			'enabled' => $this->isEnabled(),
			'outputBehavior' => $this->getOutputBehavior(),
		];
	}
}
