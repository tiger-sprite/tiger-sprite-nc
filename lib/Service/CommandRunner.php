<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Service;

class CommandRunner {
	/**
	 * @return array{exitCode: int, stdout: string, stderr: string}
	 */
	public function run(array $parts, ?string $workingDirectory = null): array {
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

		$stdout = stream_get_contents($pipes[1]) ?: '';
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]) ?: '';
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		return [
			'exitCode' => $exitCode,
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	}
}
