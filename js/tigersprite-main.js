(function(OCA) {
	'use strict'

	const appName = 'tigersprite'
	const actionName = 'tspdf'
	const permissionRead = 1
	const actionIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 260 260" fill="none"><circle cx="70" cy="70" r="32" fill="#f59e0b"/><circle cx="190" cy="70" r="32" fill="#f59e0b"/><circle cx="70" cy="70" r="18" fill="#fde68a"/><circle cx="190" cy="70" r="18" fill="#fde68a"/><circle cx="130" cy="130" r="88" fill="#f59e0b"/><ellipse cx="100" cy="152" rx="34" ry="30" fill="#fff7ed"/><ellipse cx="160" cy="152" rx="34" ry="30" fill="#fff7ed"/><circle cx="98" cy="118" r="8" fill="#111827"/><circle cx="162" cy="118" r="8" fill="#111827"/><path d="M118 142Q130 132 142 142Q136 154 130 154Q124 154 118 142Z" fill="#111827"/><path d="M130 154Q122 168 110 164" stroke="#111827" stroke-width="4" fill="none" stroke-linecap="round"/><path d="M130 154Q138 168 150 164" stroke="#111827" stroke-width="4" fill="none" stroke-linecap="round"/><path d="M130 52L118 92L130 82L142 92Z" fill="#111827"/><path d="M102 62L96 102L112 88Z" fill="#111827"/><path d="M158 62L164 102L148 88Z" fill="#111827"/><path d="M54 112L88 122L56 134Z" fill="#111827"/><path d="M206 112L172 122L204 134Z" fill="#111827"/><path d="M58 154L90 158L62 178Z" fill="#111827"/><path d="M202 154L170 158L198 178Z" fill="#111827"/><path d="M86 154H42" stroke="#111827" stroke-width="3" stroke-linecap="round"/><path d="M86 166H48" stroke="#111827" stroke-width="3" stroke-linecap="round"/><path d="M174 154H218" stroke="#111827" stroke-width="3" stroke-linecap="round"/><path d="M174 166H212" stroke="#111827" stroke-width="3" stroke-linecap="round"/></svg>'

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

		const form = document.createElement('form')
		form.method = 'POST'
		form.action = OC.generateUrl('/apps/tigersprite/download/{fileId}', { fileId })
		form.style.display = 'none'

		const tokenInput = document.createElement('input')
		tokenInput.name = 'requesttoken'
		tokenInput.value = OC.requestToken || ''
		form.appendChild(tokenInput)

		document.body.appendChild(form)
		form.submit()
		document.body.removeChild(form)
	}

	OCA.TigerSprite.saveToCurrentDirectory = async function(fileId) {
		if (!window.OC || typeof OC.generateUrl !== 'function') {
			return false
		}

		const response = await fetch(OC.generateUrl('/apps/tigersprite/save/{fileId}', { fileId }), {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				requesttoken: OC.requestToken || '',
			},
		})
		const data = await response.json().catch(() => ({}))

		if (!response.ok) {
			throw new Error(data.error || OCA.TigerSprite.t('Failed to convert file'))
		}

		if (window.OCP && OCP.Toast && typeof OCP.Toast.success === 'function') {
			OCP.Toast.success(data.message || OCA.TigerSprite.t('File successfully converted'))
		}

		return data
	}

	OCA.TigerSprite.showError = function(message) {
		if (window.OCP && OCP.Toast && typeof OCP.Toast.error === 'function') {
			OCP.Toast.error(message)
		}
	}

	OCA.TigerSprite.getEventBus = function() {
		if (window.OC && window.OC._eventBus && window._nc_event_bus === undefined) {
			window._nc_event_bus = window.OC._eventBus
		}

		const bus = window._nc_event_bus
		if (!bus || typeof bus.emit !== 'function') {
			return null
		}

		return bus
	}

	OCA.TigerSprite.emit = function(name, payload) {
		const bus = OCA.TigerSprite.getEventBus()
		if (!bus) {
			return false
		}

		bus.emit(name, payload)
		return true
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
		OCA.TigerSprite.FileClickExec(node, null, context.dir || null, context)
	}

	OCA.TigerSprite.FileClickExec = async function(node, view, dir, context) {
		const fileId = OCA.TigerSprite.getNodeFileId(node)
		if (!fileId) {
			OCA.TigerSprite.showError(OCA.TigerSprite.t('Failed to convert file'))
			return null
		}

		if (OCA.TigerSprite.setting.outputBehavior === 'save') {
			try {
				const savedFile = await OCA.TigerSprite.saveToCurrentDirectory(fileId)
				await OCA.TigerSprite.syncSavedFile(savedFile, node, view, dir, context)
			} catch (error) {
				OCA.TigerSprite.showError(error.message || OCA.TigerSprite.t('Failed to convert file'))
			}
			return null
		}

		OCA.TigerSprite.download(fileId)
		return null
	}

	OCA.TigerSprite.syncSavedFile = async function(savedFile, sourceNode, view, dir, context) {
		if (!savedFile || !savedFile.id) {
			return OCA.TigerSprite.reloadCurrentPage()
		}

		if (context && context.fileList && context.fileList.dirInfo && context.fileList.dirInfo.id === savedFile.parentId && typeof context.fileList.add === 'function') {
			context.fileList.add(savedFile, { animate: true })
			return
		}

		if (view && typeof view.getContents === 'function') {
			try {
				const viewContents = await view.getContents(dir || (sourceNode && sourceNode.dirname) || '/')
				if (viewContents && viewContents.folder && viewContents.folder.fileid === savedFile.parentId && Array.isArray(viewContents.contents)) {
					const createdNode = viewContents.contents.find((entry) => {
						const entryId = entry && (entry.fileid || entry.id || entry.fileId)
						return Number(entryId) === Number(savedFile.id)
					})

					if (createdNode && OCA.TigerSprite.emit('files:node:created', createdNode)) {
						return
					}
				}
			} catch (error) {
				// Fall back below if the active Files view cannot be synchronized incrementally.
			}
		}

		if (context && context.fileList && typeof context.fileList.reload === 'function') {
			context.fileList.reload()
			return
		}

		OCA.TigerSprite.reloadCurrentPage()
	}

	OCA.TigerSprite.reloadCurrentPage = function() {
		window.setTimeout(() => {
			if (window.location && typeof window.location.reload === 'function') {
				window.location.reload()
			}
		}, 500)
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

		;['text/markdown', 'text/x-markdown'].forEach((mime) => {
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

		const action = {
			id: actionName,
			displayName() {
				return OCA.TigerSprite.t('TigerSprite Export to PDF')
			},
			iconSvgInline() {
				return actionIcon
			},
			// NC 30+ (including NC 33) passes a context object { nodes, view, folder, contents }.
			// Older files apps pass the plain array of nodes.
			enabled(nodesOrContext) {
				const nodes = nodesOrContext && nodesOrContext.nodes ? nodesOrContext.nodes : nodesOrContext
				if (!Array.isArray(nodes) || nodes.length !== 1) {
					return false
				}

				return OCA.TigerSprite.isMarkdownNode(nodes[0]) && OCA.TigerSprite.hasReadPermission(nodes[0])
			},
			async exec(nodesOrContext) {
				const context = nodesOrContext && nodesOrContext.nodes ? nodesOrContext : null
				const node = context ? nodesOrContext.nodes[0] : nodesOrContext
				const view = context ? context.view : null
				const dir = context ? (context.folder && context.folder.path) || null : null
				return OCA.TigerSprite.FileClickExec(node, view, dir, context)
			},
			order: 35,
		}

		// Register into the @nextcloud/files v4 registry used by NC 30+ (including NC 33).
		const scope = window._nc_files_scope && window._nc_files_scope.v4_0
		if (scope && scope.fileActions instanceof Map) {
			if (!scope.fileActions.has(action.id)) {
				scope.fileActions.set(action.id, action)
				if (scope.registry && typeof scope.registry.dispatchTypedEvent === 'function') {
					scope.registry.dispatchTypedEvent('register:action', new CustomEvent('register:action', { detail: action }))
				}
			}
			OCA.TigerSprite.registeredModern = true
			return true
		}

		// Fallback: legacy registry (NC 28-32 / @nextcloud/files v3)
		if (!Array.isArray(window._nc_fileactions)) {
			window._nc_fileactions = []
		}

		if (window._nc_fileactions.some((entry) => entry && entry.id === action.id)) {
			OCA.TigerSprite.registeredModern = true
			return true
		}

		window._nc_fileactions.push(action)
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
