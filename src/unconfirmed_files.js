import Vue from 'vue/dist/vue.min'
import $ from 'jquery'

const vm = new Vue({
	el: '#app-fgft',
	data: {
		selectedFiles: [],
		allSelected: false,
	},
	methods: {
		selectOrUnselectAll() {
			this.selectedFiles = []
			if (!this.allSelected) {
				const vueInstance = this
				$('.table-unconfirmed-files .fileid').each(function(i, el) {
					vueInstance.selectedFiles.push($(el).val())
				})
			}
		},
		confirmSelectedFiles() {
			if (confirm(t('filesgfdownloadactivity', 'Are you sure you want to confirm selected files?'))) {

				const vueInstance = this

				const data = {
					files: vueInstance.selectedFiles,
				}

				$.ajax({
					url: OC.filePath('files_gf_trackdownloads', 'ajax', 'confirmSelectedSharedFiles.php'),
					type: 'POST',
					data: data,
					success: function(element) {

						// parse respone to json format
						const response = JSON.parse(element)

						if (!response.error) {
							location.reload()
						} else {
							OC.dialogs.alert(
								t('filesgfdownloadactivity', response.error_msg),
								t('filesgfdownloadactivity', 'Error')
							)
						}
					},
				})
			} else {
				return false
			}
		},
	},
})

export default vm
