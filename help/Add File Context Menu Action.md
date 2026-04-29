# Nextcloud App Notes

## Add File Context Menu Action

This is a practical guide for adding a custom right-click file menu action in a Nextcloud app. The examples are based on the working TigerSprite implementation and follow the same general loading flow used by ONLYOFFICE.

### 1. Register the Files script listener

In `apps/your_app/lib/AppInfo/Application.php`, register a listener for `OCA\Files\Event\LoadAdditionalScriptsEvent`.

```php
<?php

declare(strict_types=1);

namespace OCA\YourApp\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\YourApp\Listeners\FilesListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'your_app';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, FilesListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
```

### 2. Add the Files listener

Create `apps/your_app/lib/Listeners/FilesListener.php`.

This listener is called when the Files app loads additional scripts. Provide frontend state first, then add the JavaScript file.

```php
<?php

declare(strict_types=1);

namespace OCA\YourApp\Listeners;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\YourApp\AppInfo\Application;
use OCA\YourApp\Service\ConfigService;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class FilesListener implements IEventListener {
	public function __construct(
		private ConfigService $configService,
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		$this->initialState->provideLazyInitialState('settings', function (): array {
			return $this->configService->getFrontendState();
		});

		Util::addScript(Application::APP_ID, 'your-app-main');
	}
}
```

The script above loads:

```text
apps/your_app/js/your-app-main.js
```

### 3. Provide frontend state

Your config service should expose only the small amount of state needed by the file action.

```php
public function getFrontendState(): array {
	return [
		'enabled' => $this->isEnabled(),
	];
}
```

The backend controller must still check the same setting before executing the action. Hiding a menu item in JavaScript is only a UI decision, not a security boundary.

### 4. Add the JavaScript action file

Create `apps/your_app/js/your-app-main.js`.

Use an `OCA.YourApp` namespace. This is similar to ONLYOFFICE's `OCA.Onlyoffice` style and makes browser debugging much easier.

```js
(function(OCA) {
	'use strict'

	const appName = 'your_app'
	const actionName = 'youraction'
	const permissionRead = 1
	const actionIcon = '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M4 3h12v14H4z"/></svg>'

	OCA.YourApp = Object.assign({
		AppName: appName,
		actionName,
		setting: { enabled: true },
		registeredLegacy: false,
		registeredModern: false,
	}, OCA.YourApp || {})

	OCA.YourApp.t = function(text) {
		return window.t ? window.t(OCA.YourApp.AppName, text) : text
	}

	OCA.YourApp.loadSettings = function() {
		if (window.OCP && OCP.InitialState && typeof OCP.InitialState.loadState === 'function') {
			OCA.YourApp.setting = OCP.InitialState.loadState(OCA.YourApp.AppName, 'settings', {
				enabled: true,
			})
		}
	}

	OCA.YourApp.isEnabled = function() {
		return OCA.YourApp.setting.enabled !== false
	}

	OCA.YourApp.isTargetFile = function(node) {
		if (!node) {
			return false
		}

		const mime = String(node.mime || node.mimetype || '').toLowerCase()
		const extension = String(node.extension || '').toLowerCase().replace(/^\./, '')
		const name = String(node.basename || node.name || node.source || '').toLowerCase()

		return mime === 'text/markdown'
			|| mime === 'text/x-markdown'
			|| extension === 'md'
			|| extension === 'markdown'
			|| name.endsWith('.md')
			|| name.endsWith('.markdown')
	}

	OCA.YourApp.getFileId = function(node) {
		return node && (node.fileid || node.id || node.fileId || null)
	}

	OCA.YourApp.runAction = async function(node) {
		const fileId = OCA.YourApp.getFileId(node)
		if (!fileId) {
			if (window.OCP && OCP.Toast) {
				OCP.Toast.error(OCA.YourApp.t('Failed to run file action'))
			}
			return null
		}

		window.location.href = OC.generateUrl('/apps/your_app/action/{fileId}', { fileId })
		return null
	}

	OCA.YourApp.legacyActionHandler = function(fileName, context) {
		const model = context.fileInfoModel || (context.fileList && context.fileList.getModelForFile(fileName))
		const node = {
			fileid: context.fileId || (context.$file && context.$file[0] && context.$file[0].dataset.id) || (model && model.id),
			basename: fileName,
			permissions: (model && model.permissions) || permissionRead,
		}

		if (OCA.YourApp.isTargetFile(node)) {
			OCA.YourApp.runAction(node)
		}
	}

	OCA.YourApp.registerLegacyAction = function() {
		if (OCA.YourApp.registeredLegacy) {
			return true
		}

		if (!OCA.YourApp.isEnabled() || !OCA.Files || !OCA.Files.fileActions || !window.OC) {
			return false
		}

		;['text/markdown', 'text/x-markdown', 'text/plain'].forEach((mime) => {
			OCA.Files.fileActions.registerAction({
				name: actionName,
				displayName: OCA.YourApp.t('Export to PDF'),
				mime,
				permissions: OC.PERMISSION_READ || permissionRead,
				iconSvgInline() {
					return actionIcon
				},
				actionHandler: OCA.YourApp.legacyActionHandler,
			})
		})

		OCA.YourApp.registeredLegacy = true
		return true
	}

	OCA.YourApp.registerModernAction = function() {
		if (OCA.YourApp.registeredModern) {
			return true
		}

		if (!OCA.YourApp.isEnabled()) {
			return false
		}

		if (!Array.isArray(window._nc_fileactions)) {
			window._nc_fileactions = []
		}

		if (window._nc_fileactions.some((action) => action && action.id === actionName)) {
			OCA.YourApp.registeredModern = true
			return true
		}

		window._nc_fileactions.push({
			id: actionName,
			displayName() {
				return OCA.YourApp.t('Export to PDF')
			},
			iconSvgInline() {
				return actionIcon
			},
			enabled(nodes) {
				return Array.isArray(nodes)
					&& nodes.length === 1
					&& OCA.YourApp.isTargetFile(nodes[0])
			},
			exec: OCA.YourApp.runAction,
			order: 35,
		})

		OCA.YourApp.registeredModern = true
		return true
	}

	OCA.YourApp.registerAction = function() {
		OCA.YourApp.loadSettings()

		if (!OCA.YourApp.isEnabled()) {
			return
		}

		OCA.YourApp.registerLegacyAction()
		OCA.YourApp.registerModernAction()
	}

	OCA.YourApp.registerAction()

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', OCA.YourApp.registerAction, { once: true })
	} else {
		window.setTimeout(OCA.YourApp.registerAction, 0)
	}

	window.addEventListener('load', OCA.YourApp.registerAction, { once: true })
})(window.OCA = window.OCA || {})
```

### 5. Important details

Use a short internal action id.

For example, use `tspdf`, `exportpdf`, or `mdpdf`. The visible label can be longer, such as `TigerSprite Export to PDF`.

Register both action systems.

Nextcloud 31/32 can involve both the older `OCA.Files.fileActions.registerAction()` path and the newer `window._nc_fileactions` path. A robust app should register both.

Keep `enabled()` tolerant.

Do not rely too heavily on fields like `node.permissions`. In some Nextcloud 32 file-list contexts, third-party actions may receive nodes where optional fields are missing. If `enabled()` returns `false`, the menu item simply disappears without a console error.

Match files using multiple hints.

Use MIME, extension, and basename/path checks:

```js
node.mime
node.mimetype
node.extension
node.basename
node.name
node.source
```

Do not trust frontend enable/disable for security.

Even if the menu is hidden, the backend route must re-check app settings, file permissions, and file type.

Bump the app version after changing JavaScript.

Nextcloud can cache app assets. After changing the action script, update `appinfo/info.xml`:

```xml
<version>0.1.1</version>
```

Then re-enable, upgrade, or refresh the app in the target environment.

### 6. Debugging

Open the Files app, then check the browser console:

```js
OCA.YourApp
window._nc_fileactions?.filter((action) => action.id === 'youraction')
```

For TigerSprite specifically:

```js
OCA.TigerSprite
window._nc_fileactions?.filter((action) => action.id === 'tspdf')
```

If the namespace exists but `_nc_fileactions` does not contain the action, the script loaded but registration did not run successfully.

If `_nc_fileactions` contains the action but the menu does not appear, inspect the `enabled()` function and the node fields passed by the current Files view.

### 7. Why TigerSprite failed before

The first versions failed for several practical reasons:

1. The JavaScript was not structured like the working ONLYOFFICE flow, so it was harder to inspect and retry from the browser console.

2. The action only partially covered the old/new Files action APIs. Nextcloud 31/32 may use different action paths depending on the current Files UI.

3. The modern action used a long internal id and stricter checks than necessary. The long id was not proven to be the direct cause, but shortening it removed a variable.

4. The `enabled()` logic depended too much on fields like `node.permissions`. When those fields were missing, Nextcloud hid the menu without showing a console error.

5. Missing or stale frontend state could cause the script to skip registration. The final version defaults to registering unless the frontend explicitly receives `enabled: false`, while the backend still performs the real permission and enable checks.

6. Browser and Nextcloud asset caching made it easy to keep testing an older JavaScript file. Bumping the app version helped force the new script to load.
