/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
// import Vue from 'vue'
import $ from 'jquery'

(function(OCA) {

	OCA.FilesGFTrackDownloads = {
		AppName: 'files_gf_trackdownloads',
		context: null,
		folderUrl: null,
	}

	OCA.FilesGFTrackDownloads.MarkAsConfirmed = function(filename, context) {

		const fileID = context.fileInfoModel.id

		const data = {
			fileID: fileID,
		}

		// set table row of file/folder as busy
		const tr = context.fileList.findFileEl(filename)
		context.fileList.showFileBusyState(tr, true)

		$.ajax({
			url: OC.filePath('files_gf_trackdownloads', 'ajax', 'confirm.php'),
			type: 'POST',
			data: data,
			success: function(element) {

				// set table row of file/folder as busy
				context.fileList.showFileBusyState(tr, false)

				// parse respone to json format
				const response = JSON.parse(element)

				if (!response.error) {
					context.fileList.reload()
				} else {
					OC.dialogs.alert(
						t('filesgfdownloadactivity', response.error_msg),
						t('filesgfdownloadactivity', 'Error'),
					)
				}
			},
		})
	}

	OCA.FilesGFTrackDownloads.FileList = {
		attach: function(fileList) {
			fileList.fileActions.registerAction({
				name: 'confirmobject',
				displayName: t('filesgfdownloadactivity', 'Confirm'),
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				iconClass: 'icon-fgft-confirmation',
				actionHandler: OCA.FilesGFTrackDownloads.MarkAsConfirmed,
			})
		},
	}

	const initPage = function() {
		OC.Plugins.register('OCA.Files.FileList', OCA.FilesGFTrackDownloads.FileList)
	}

	$(document).ready(initPage)

})(OCA)

/*
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
					vueInstance.selectedFiles.push(el.val())
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
*/
