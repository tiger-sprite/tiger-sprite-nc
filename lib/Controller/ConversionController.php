<?php

declare(strict_types=1);

namespace OCA\Tigersprite\Controller;

use OCA\Tigersprite\AppInfo\Application;
use OCA\Tigersprite\Service\ConfigService;
use OCA\Tigersprite\Service\TigerSpriteConversionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IL10N;

class ConversionController extends Controller {
	public function __construct(
		IRequest $request,
		private IRootFolder $rootFolder,
		private TigerSpriteConversionService $conversionService,
		private ConfigService $configService,
		private IL10N $l10n,
		private ?string $userId,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function download(int $fileId): DataDownloadResponse {
		$this->ensureEnabled();
		$file = $this->getMarkdownFile($fileId);
		$result = $this->conversionService->convertFileToPdf($file);
		try {
			$content = file_get_contents($result['pdfPath']);
			if ($content === false) {
				throw new \RuntimeException($this->l10n->t('Failed to convert file.'));
			}
			return new DataDownloadResponse($content, $result['pdfName'], 'application/pdf');
		} finally {
			$this->conversionService->cleanupWorkDirectory($result['workDir']);
		}
	}

	#[NoAdminRequired]
	public function save(int $fileId): DataResponse {
		try {
			$this->ensureEnabled();
			$file = $this->getMarkdownFile($fileId);
			$parent = $file->getParent();
			if (!$parent->isCreatable()) {
				return new DataResponse([
					'error' => $this->l10n->t('You do not have permission to create a file in this directory.'),
				], Http::STATUS_FORBIDDEN);
			}

			$result = $this->conversionService->convertFileToPdf($file);
			try {
				$targetName = $this->createTargetName($parent, $result['pdfName']);
				$newFile = $parent->newFile($targetName);
				$content = file_get_contents($result['pdfPath']);
				if ($content === false) {
					throw new \RuntimeException($this->l10n->t('Failed to convert file.'));
				}
				$newFile->putContent($content);

				return new DataResponse([
					'id' => $newFile->getId(),
					'name' => $newFile->getName(),
					'parentId' => $parent->getId(),
					'mimetype' => $newFile->getMimeType(),
					'message' => $this->l10n->t('File successfully converted'),
				]);
			} finally {
				$this->conversionService->cleanupWorkDirectory($result['workDir']);
			}
		} catch (\RuntimeException $e) {
			return new DataResponse([
				'error' => $e->getMessage(),
			], Http::STATUS_BAD_REQUEST);
		}
	}

	private function ensureEnabled(): void {
		if (!$this->configService->isEnabled()) {
			throw new \RuntimeException($this->l10n->t('TigerSprite is disabled.'));
		}
	}

	private function getMarkdownFile(int $fileId): File {
		if ($this->userId === null) {
			throw new \RuntimeException($this->l10n->t('User session is not available.'));
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$node = $userFolder->getFirstNodeById($fileId);
		if (!($node instanceof File) || !$node->isReadable()) {
			throw new \RuntimeException($this->l10n->t('The file cannot be found.'));
		}

		$extension = strtolower(pathinfo($node->getName(), PATHINFO_EXTENSION));
		if (!in_array($extension, ['md', 'markdown'], true)) {
			throw new \RuntimeException($this->l10n->t('TigerSprite supports only Markdown files.'));
		}

		return $node;
	}

	private function createTargetName($folder, string $preferredName): string {
		$info = pathinfo($preferredName);
		$base = $info['filename'] ?? 'document';
		$extension = isset($info['extension']) ? '.' . $info['extension'] : '';

		$candidate = $base . $extension;
		$counter = 1;
		while ($folder->nodeExists($candidate)) {
			$candidate = sprintf('%s (%d)%s', $base, $counter, $extension);
			$counter++;
		}

		return $candidate;
	}
}
