(function() {
	'use strict'

	document.addEventListener('DOMContentLoaded', function() {
		var form = document.getElementById('tigersprite-admin-form')
		if (!form) return

		var saveButton = form.querySelector('.tigersprite-save-btn')
		if (!saveButton) return

		var originalText = saveButton.textContent

		form.addEventListener('submit', async function(e) {
			e.preventDefault()

			saveButton.disabled = true
			saveButton.innerHTML = '<span class="icon-loading-small"></span> ' + originalText

			var formData = new FormData(form)

			try {
				var response = await fetch(OC.generateUrl('/apps/tigersprite/settings/save'), {
					method: 'POST',
					body: formData,
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
					},
				})

				var data = await response.json()

				if (response.ok && data.success) {
					OCP.Toast.success(t('tigersprite', 'Settings saved successfully'))
				} else {
					throw new Error(data.message || t('tigersprite', 'Failed to save settings'))
				}
			} catch (error) {
				OCP.Toast.error(error.message || t('tigersprite', 'Failed to save settings'))
			} finally {
				saveButton.disabled = false
				saveButton.textContent = originalText
			}
		})
	})
})()
