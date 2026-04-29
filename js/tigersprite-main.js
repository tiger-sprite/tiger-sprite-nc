(function(OCA) {
	'use strict'

	const appName = 'tigersprite'
	const actionName = 'tspdf'
	const permissionRead = 1
	const actionIcon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="16" height="16" rx="3" fill="#D97706"/><path d="M6 6.5H12.5V8H6V6.5Z" fill="white"/><path d="M6 9.5H14V11H6V9.5Z" fill="white"/><path d="M6 12.5H11V14H6V12.5Z" fill="white"/><path d="M14.5 12V15.5L17 13.75L14.5 12Z" fill="white"/></svg>'

	OCA.TigerSprite = Object.assign({
		AppName: appName,
		actionName,
		setting: { enabled: true, outputBehavior: 'download' },
		registeredLegacy: false,
		registeredModern: false,
	}, OCA.TigerSprite || {})

	OCA.TigerSprite.t = function(text) {
		return window.t ? window.t(OCA.TigerSprite.AppName, text) : text
	}

	OCA.TigerSprite.loadSettings = function() {
		if (window.OCP && OCP.InitialState && typeof OCP.InitialState.loadState === 'function') {
			OCA.TigerSprite.setting = OCP.InitialState.loadState(OCA.TigerSprite.AppName, 'settings', {
				enabled: true,
				outputBehavior: 'download',
			})
		}
	}

	OCA.TigerSprite.isEnabled = function() {
		return OCA.TigerSprite.setting.enabled !== false
	}

	OCA.TigerSprite.isMarkdownName = function(name) {
		const lower = String(name || '').toLowerCase()
		return lower.endsWith('.md') || lower.endsWith('.markdown')
	}

	OCA.TigerSprite.isMarkdownNode = function(node) {
		if (!node) {
			return false
		}

		const mime = String(node.mime || node.mimetype || '').toLowerCase()
		if (mime === 'text/markdown' || mime === 'text/x-markdown') {
			return true
		}

		const extension = String(node.extension || '').toLowerCase().replace(/^\./, '')
		if (extension === 'md' || extension === 'markdown') {
			return true
		}

		return OCA.TigerSprite.isMarkdownName(node.basename || node.name || node.source || '')
	}

	OCA.TigerSprite.hasReadPermission = function(node) {
		if (!node || node.permissions === undefined || node.permissions === null) {
			return true
		}

		return (Number(node.permissions) & permissionRead) === permissionRead
	}

	OCA.TigerSprite.getNodeFileId = function(node) {
		return node && (node.fileid || node.id || node.fileId || null)
	}

	OCA.TigerSprite.download = function(fileId) {
		if (!window.OC || typeof OC.generateUrl !== 'function') {
			return
		}

		window.location.href = OC.generateUrl('/apps/tigersprite/download/{fileId}', { fileId })
	}

	OCA.TigerSprite.showError = function(message) {
		if (window.OCP && OCP.Toast && typeof OCP.Toast.error === 'function') {
			OCP.Toast.error(message)
		}
	}

	OCA.TigerSprite.FileClick = function(fileName, context) {
		if (!OCA.TigerSprite.isMarkdownName(fileName)) {
			return
		}

		const model = context.fileInfoModel || (context.fileList && context.fileList.getModelForFile(fileName))
		const node = {
			fileid: context.fileId || (context.$file && context.$file[0] && context.$file[0].dataset.id) || (model && model.id),
			basename: fileName,
			permissions: (model && model.attributes && model.attributes.permissions) || (model && model.permissions) || permissionRead,
		}
		OCA.TigerSprite.FileClickExec(node)
	}

	OCA.TigerSprite.FileClickExec = async function(node) {
		const fileId = OCA.TigerSprite.getNodeFileId(node)
		if (!fileId) {
			OCA.TigerSprite.showError(OCA.TigerSprite.t('Failed to convert file'))
			return null
		}

		OCA.TigerSprite.download(fileId)
		return null
	}

	OCA.TigerSprite.registerLegacyAction = function() {
		if (OCA.TigerSprite.registeredLegacy) {
			return true
		}

		if (!OCA.TigerSprite.isEnabled() || !OCA.Files || !OCA.Files.fileActions || !window.OC) {
			return false
		}

		if (typeof OCA.Files.fileActions.get === 'function') {
			const existing = OCA.Files.fileActions.get('text/markdown', actionName)
			if (existing) {
				OCA.TigerSprite.registeredLegacy = true
				return true
			}
		}

		;['text/markdown', 'text/x-markdown', 'text/plain'].forEach((mime) => {
			OCA.Files.fileActions.registerAction({
				name: actionName,
				displayName: OCA.TigerSprite.t('TigerSprite Export to PDF'),
				mime,
				permissions: OC.PERMISSION_READ || permissionRead,
				iconSvgInline() {
					return actionIcon
				},
				actionHandler: OCA.TigerSprite.FileClick,
			})
		})

		OCA.TigerSprite.registeredLegacy = true
		return true
	}

	OCA.TigerSprite.registerModernAction = function() {
		if (OCA.TigerSprite.registeredModern) {
			return true
		}

		if (!OCA.TigerSprite.isEnabled()) {
			return false
		}

		if (!Array.isArray(window._nc_fileactions)) {
			window._nc_fileactions = []
		}

		if (window._nc_fileactions.some((action) => action && action.id === actionName)) {
			OCA.TigerSprite.registeredModern = true
			return true
		}

		window._nc_fileactions.push({
			id: actionName,
			displayName() {
				return OCA.TigerSprite.t('TigerSprite Export to PDF')
			},
			iconSvgInline() {
				return actionIcon
			},
			enabled(nodes) {
				if (!Array.isArray(nodes) || nodes.length !== 1) {
					return false
				}

				return OCA.TigerSprite.isMarkdownNode(nodes[0]) && OCA.TigerSprite.hasReadPermission(nodes[0])
			},
			exec: OCA.TigerSprite.FileClickExec,
			order: 35,
		})

		OCA.TigerSprite.registeredModern = true
		return true
	}

	OCA.TigerSprite.registerAction = function() {
		OCA.TigerSprite.loadSettings()

		if (!OCA.TigerSprite.isEnabled()) {
			return
		}

		OCA.TigerSprite.registerLegacyAction()
		OCA.TigerSprite.registerModernAction()
	}

	OCA.TigerSprite.registerAction()

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', OCA.TigerSprite.registerAction, { once: true })
	} else {
		window.setTimeout(OCA.TigerSprite.registerAction, 0)
	}

	window.addEventListener('load', OCA.TigerSprite.registerAction, { once: true })
})(window.OCA = window.OCA || {})
