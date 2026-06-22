<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Service;

class CommandRunner {
	public const DEFAULT_TIMEOUT = 120;

	/**
	 * @param array<int, string> $parts
	 * @return array{exitCode: int, stdout: string, stderr: string}
	 */
	public function run(array $parts, ?string $workingDirectory = null, int $timeout = self::DEFAULT_TIMEOUT): array {
		$escaped = array_map('escapeshellarg', $parts);
		$command = implode(' ', $escaped);

		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory);
		if (!is_resource($process)) {
			throw new \RuntimeException('Failed to start TigerSprite CLI process.');
		}

		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		$startTime = time();

		while (true) {
			if (time() - $startTime > $timeout) {
				proc_terminate($process, 9);
				foreach ($pipes as $pipe) {
					fclose($pipe);
				}
				proc_close($process);
				throw new \RuntimeException('TigerSprite CLI process timed out after ' . $timeout . ' seconds.');
			}

			$stdout .= stream_get_contents($pipes[1]) ?: '';
			$stderr .= stream_get_contents($pipes[2]) ?: '';

			$status = proc_get_status($process);
			if ($status !== false && !$status['running']) {
				break;
			}

			usleep(100_000);
		}

		$stdout .= stream_get_contents($pipes[1]) ?: '';
		$stderr .= stream_get_contents($pipes[2]) ?: '';

		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$exitCode = proc_close($process);

		return [
			'exitCode' => $exitCode,
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	}
}
