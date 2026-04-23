<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Conversion;

use OCA\Tigersprite\Service\TigerSpriteConversionService;
use OCP\Files\Conversion\ConversionMimeProvider;
use OCP\Files\Conversion\IConversionProvider;
use OCP\Files\File;

class TigerSpriteConversionProvider implements IConversionProvider {
	public function __construct(
		private TigerSpriteConversionService $conversionService,
	) {
	}

	public function getSupportedMimeTypes(): array {
		return [
			new ConversionMimeProvider('text/markdown', 'application/pdf', 'pdf', 'PDF (.pdf)'),
			new ConversionMimeProvider('text/x-markdown', 'application/pdf', 'pdf', 'PDF (.pdf)'),
		];
	}

	public function convertFile(File $file, string $targetMimeType): mixed {
		if ($targetMimeType !== 'application/pdf') {
			throw new \InvalidArgumentException('Unsupported target MIME type: ' . $targetMimeType);
		}

		$result = $this->conversionService->convertFileToPdf($file);
		try {
			return file_get_contents($result['pdfPath']);
		} finally {
			$this->conversionService->cleanupWorkDirectory($result['workDir']);
		}
	}
}
