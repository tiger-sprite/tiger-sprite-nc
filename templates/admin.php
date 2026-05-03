<?php
/**
 * @var \OCP\IL10N $l
 * @var array $_
 */

$config = $_['config'];
?>

<div class="section">
<div id="tigersprite-admin">
	<div class="tigersprite-header">
		<div class="tigersprite-header-icon">
			<picture>
				<source srcset="<?php p(\OC::$server->getURLGenerator()->imagePath('tigersprite', 'app-dark.svg')); ?>" media="(prefers-color-scheme: dark)">
				<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('tigersprite', 'app.svg')); ?>" alt="<?php p($l->t('TigerSprite')); ?>">
			</picture>
		</div>
		<div class="tigersprite-header-text">
			<h2><?php p($l->t('TigerSprite')); ?></h2>
			<p><?php p($l->t('Configure TigerSprite Markdown to PDF conversion for files.')); ?></p>
		</div>
	</div>

	<form id="tigersprite-admin-form" method="post" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('tigersprite.settings.save')); ?>">
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">

		<div class="tigersprite-settings-card">
			<h3 class="tigersprite-card-title"><?php p($l->t('General')); ?></h3>

			<div class="tigersprite-checkbox-field">
				<input type="hidden" name="enabled" value="0">
				<input type="checkbox" id="tigersprite-enabled" name="enabled" value="1" <?php if ($config['enabled']) { print_unescaped('checked'); } ?>>
				<div class="checkbox-content">
					<label class="checkbox-label" for="tigersprite-enabled"><?php p($l->t('Enable TigerSprite')); ?></label>
					<span class="checkbox-hint"><?php p($l->t('Enable or disable the TigerSprite PDF export functionality for Markdown files.')); ?></span>
				</div>
			</div>
		</div>

		<div class="tigersprite-settings-card">
			<h3 class="tigersprite-card-title"><?php p($l->t('Runtime')); ?></h3>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-runtime-mode"><?php p($l->t('Runtime mode')); ?></label>
				<select id="tigersprite-runtime-mode" name="runtime_mode" disabled>
					<option value="cli" selected><?php p($l->t('CLI')); ?></option>
				</select>
			</div>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-php-path"><?php p($l->t('PHP executable path')); ?></label>
				<input id="tigersprite-php-path" name="php_path" type="text" class="settings-input" value="<?php p($config['phpPath']); ?>">
				<span class="tigersprite-field-hint"><?php p($l->t('Path to the PHP binary (e.g., /usr/bin/php or php).')); ?></span>
			</div>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-cli-path"><?php p($l->t('TigerSprite CLI path')); ?></label>
				<input id="tigersprite-cli-path" name="cli_path" type="text" class="settings-input" value="<?php p($config['cliPath']); ?>">
				<span class="tigersprite-field-hint"><?php p($l->t('Path to the TigerSprite CLI entry script (cli.php).')); ?></span>
			</div>
		</div>

		<div class="tigersprite-settings-card">
			<h3 class="tigersprite-card-title"><?php p($l->t('PDF Settings')); ?></h3>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-generator"><?php p($l->t('PDF generator')); ?></label>
				<select id="tigersprite-generator" name="generator">
					<option value="auto" <?php if ($config['generator'] === 'auto') { print_unescaped('selected'); } ?>><?php p($l->t('Auto')); ?></option>
					<option value="wkhtmltopdf" <?php if ($config['generator'] === 'wkhtmltopdf') { print_unescaped('selected'); } ?>>wkhtmltopdf</option>
					<option value="dompdf" <?php if ($config['generator'] === 'dompdf') { print_unescaped('selected'); } ?>>dompdf</option>
				</select>
				<span class="tigersprite-field-hint"><?php p($l->t('Choose the PDF generation engine. "Auto" will try wkhtmltopdf first, then fall back to dompdf.')); ?></span>
			</div>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-output"><?php p($l->t('Output behavior')); ?></label>
				<select id="tigersprite-output" name="output_behavior">
					<option value="download" <?php if ($config['outputBehavior'] === 'download') { print_unescaped('selected'); } ?>><?php p($l->t('Download')); ?></option>
					<option value="save" <?php if ($config['outputBehavior'] === 'save') { print_unescaped('selected'); } ?>><?php p($l->t('Save to current directory')); ?></option>
				</select>
				<span class="tigersprite-field-hint"><?php p($l->t('Whether to download the PDF directly or save it alongside the Markdown file.')); ?></span>
			</div>

			<div class="tigersprite-field">
				<label class="tigersprite-field-label" for="tigersprite-retention"><?php p($l->t('Cache retention days')); ?></label>
				<input id="tigersprite-retention" name="retention_days" type="number" min="0" value="<?php p((string)$config['retentionDays']); ?>">
				<span class="tigersprite-field-hint"><?php p($l->t('Number of days to keep temporary conversion files before cleanup (0 = disable cleanup).')); ?></span>
			</div>
		</div>

		<div class="tigersprite-settings-card">
			<h3 class="tigersprite-card-title"><?php p($l->t('Debug')); ?></h3>

			<div class="tigersprite-checkbox-field">
				<input type="hidden" name="debug_logging" value="0">
				<input type="checkbox" id="tigersprite-debug" name="debug_logging" value="1" <?php if ($config['debugLogging']) { print_unescaped('checked'); } ?>>
				<div class="checkbox-content">
					<label class="checkbox-label" for="tigersprite-debug"><?php p($l->t('Enable debug logging')); ?></label>
					<span class="checkbox-hint"><?php p($l->t('Log detailed conversion information to the Nextcloud log for troubleshooting.')); ?></span>
				</div>
			</div>
		</div>

		<div class="tigersprite-save-bar">
			<button type="submit" class="primary tigersprite-save-btn"><?php p($l->t('Save')); ?></button>
		</div>
	</form>
</div>
</div>
