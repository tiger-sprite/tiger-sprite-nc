(function(OCA, OC, OCP) {
	'use strict'

	if (!OCA) {
		return
	}

	const appName = 'tigersprite'
	const settings = OCP.InitialState.loadState(appName, 'settings', { enabled: false, outputBehavior: 'download' })

	function isMarkdownName(name) {
		const lower = (name || '').toLowerCase()
		return lower.endsWith('.md') || lower.endsWith('.markdown')
	}

	function handleDownload(fileId) {
		window.location.href = OC.generateUrl('/apps/tigersprite/download/{fileId}', { fileId })
	}

	function handleSave(fileId) {
		fetch(OC.generateUrl('/apps/tigersprite/save/{fileId}', { fileId }), {
			method: 'POST',
			headers: {
				'requesttoken': OC.requestToken,
			},
		})
			.then(async (response) => {
				let data = {}
				try {
					data = await response.json()
				} catch (error) {
					data = {}
				}
				if (!response.ok || data.error) {
					throw new Error(data.error || t(appName, 'Failed to convert file'))
				}
				OCP.Toast.success(data.message || t(appName, 'File successfully converted'))
				window.setTimeout(() => window.location.reload(), 500)
			})
			.catch((error) => {
				OCP.Toast.error(error.message || t(appName, 'Failed to convert file'))
			})
	}

	function registerLegacyAction() {
		if (!OCA.Files || !OCA.Files.fileActions || !settings.enabled) {
			return
		}

		// V1 intentionally registers a per-file action only.
		// Multi-select export is out of scope and should not appear in the files UI.
		const mimeTypes = ['text/markdown', 'text/x-markdown']
		mimeTypes.forEach((mime) => {
			OCA.Files.fileActions.registerAction({
				name: 'tigerspriteExportPdf',
				displayName: t(appName, 'TigerSprite Export to PDF'),
				mime,
				permissions: OC.PERMISSION_READ,
				actionHandler(fileName, context) {
					if (!isMarkdownName(fileName)) {
						return
					}
					const model = context.fileInfoModel || (context.fileList && context.fileList.getModelForFile(fileName))
					const fileId = context.fileId || (context.$file && context.$file[0] && context.$file[0].dataset.id) || (model && model.id)
					if (!fileId) {
						OCP.Toast.error(t(appName, 'Failed to convert file'))
						return
					}
					if (settings.outputBehavior === 'save') {
						handleSave(fileId)
						return
					}
					handleDownload(fileId)
				},
			})
		})
	}

	registerLegacyAction()
})(OCA, OC, OCP)
