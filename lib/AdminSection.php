<?php

declare(strict_types=1);

namespace OCA\Tigersprite;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('tigersprite', 'app-dark.svg');
	}

	public function getID(): string {
		return 'tigersprite';
	}

	public function getName(): string {
		return $this->l10n->t('TigerSprite');
	}

	public function getPriority(): int {
		return 60;
	}
}
