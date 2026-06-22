<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#save', 'url' => '/settings/save', 'verb' => 'POST'],
		['name' => 'conversion#download', 'url' => '/download/{fileId}', 'verb' => 'POST'],
		['name' => 'conversion#save', 'url' => '/save/{fileId}', 'verb' => 'POST'],
	],
];
