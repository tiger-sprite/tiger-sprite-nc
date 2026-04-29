<?php
/**
 * @var \OCP\IL10N $l
 * @var array $_
 */

$config = $_['config'];
?>

<div class="section">
	<h2><?php p($l->t('TigerSprite')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Configure TigerSprite Markdown to PDF conversion for files.')); ?></p>

	<form method="post" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('tigersprite.settings.save')); ?>">
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">

		<p>
			<label>
				<input type="checkbox" name="enabled" value="1" <?php if ($config['enabled']) { print_unescaped('checked'); } ?>>
				<?php p($l->t('Enable TigerSprite')); ?>
			</label>
		</p>

		<p>
			<label for="tigersprite-runtime-mode"><?php p($l->t('Runtime mode')); ?></label><br>
			<select id="tigersprite-runtime-mode" name="runtime_mode" disabled>
				<option value="cli" selected><?php p($l->t('CLI')); ?></option>
			</select>
		</p>

		<p>
			<label for="tigersprite-php-path"><?php p($l->t('PHP executable path')); ?></label><br>
			<input id="tigersprite-php-path" name="php_path" type="text" class="settings-input" value="<?php p($config['phpPath']); ?>">
		</p>

		<p>
			<label for="tigersprite-cli-path"><?php p($l->t('TigerSprite CLI path')); ?></label><br>
			<input id="tigersprite-cli-path" name="cli_path" type="text" class="settings-input" value="<?php p($config['cliPath']); ?>">
		</p>

		<p>
			<label for="tigersprite-generator"><?php p($l->t('PDF generator')); ?></label><br>
			<select id="tigersprite-generator" name="generator">
				<option value="auto" <?php if ($config['generator'] === 'auto') { print_unescaped('selected'); } ?>><?php p($l->t('Auto')); ?></option>
				<option value="wkhtmltopdf" <?php if ($config['generator'] === 'wkhtmltopdf') { print_unescaped('selected'); } ?>>wkhtmltopdf</option>
				<option value="dompdf" <?php if ($config['generator'] === 'dompdf') { print_unescaped('selected'); } ?>>dompdf</option>
			</select>
		</p>

		<p>
			<label for="tigersprite-output"><?php p($l->t('Output behavior')); ?></label><br>
			<select id="tigersprite-output" name="output_behavior">
				<option value="download" <?php if ($config['outputBehavior'] === 'download') { print_unescaped('selected'); } ?>><?php p($l->t('Download')); ?></option>
				<option value="save" <?php if ($config['outputBehavior'] === 'save') { print_unescaped('selected'); } ?>><?php p($l->t('Save to current directory')); ?></option>
			</select>
		</p>

		<p>
			<label for="tigersprite-retention"><?php p($l->t('Cache retention days')); ?></label><br>
			<input id="tigersprite-retention" name="retention_days" type="number" min="0" value="<?php p((string)$config['retentionDays']); ?>">
		</p>

		<p>
			<label>
				<input type="checkbox" name="debug_logging" value="1" <?php if ($config['debugLogging']) { print_unescaped('checked'); } ?>>
				<?php p($l->t('Enable debug logging')); ?>
			</label>
		</p>

		<p>
			<button type="submit" class="primary"><?php p($l->t('Save')); ?></button>
		</p>
	</form>
</div>
