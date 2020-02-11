/**
 * @copyright Copyright (c) 2020 Milos Petkovic <milos.petkovic@elb-solutions.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

//import { AppNavigation } from '@nextcloud/vue'

(function (OCA) {

    OCA.FilesGFTrackDownloads = _.extend({
        AppName: "files_gf_trackdownloads",
        context: null,
        folderUrl: null
    }, OCA.FilesGFTrackDownloads);

    OCA.FilesGFTrackDownloads.MarkAsConfirmed = function (filename, context) {

        // console.log('ime fajla koji pozivam:', fileName);
        console.log('context koji pozivam:', context);
        //console.log('get id od fajla: ', context.fileInfoModel.id);

        var fileID = context.fileInfoModel.id;

        //var dir = context.dir || context.fileList.getCurrentDirectory();
        //var isDir = context.$file.attr('data-type') === 'dir';
        //var url = context.fileList.getDownloadUrl(fileName, dir, isDir);
        //console.log(dir, isDir);

        var data = {
            //nameOfFile: filename,
            fileID: fileID
        };

        // set table row of file/folder as busy
        var tr = context.fileList.findFileEl(filename);
        context.fileList.showFileBusyState(tr, true);

        $.ajax({
            url: OC.filePath('files_gf_trackdownloads', 'ajax','confirm.php'),
            type: 'POST',
            data: data,
            success: function(element) {

                // set table row of file/folder as busy
                context.fileList.showFileBusyState(tr, false);

                // parse respone to json format
                var response = JSON.parse(element);

                if (!response.error) {
                    context.fileList.reload();
                } else {
                    OC.dialogs.alert(
                        t('filesgfdownloadactivity', response.error_msg),
                        t('filesgfdownloadactivity', 'Error')
                    );
                }
            }
        });
    };

    OCA.FilesGFTrackDownloads.setting = {};

    OCA.FilesGFTrackDownloads.GetSettings = function (callbackSettings) {
        $.get(OC.generateUrl("apps/" + OCA.FilesGFTrackDownloads.AppName + "/ajax/settings"),
            function onSuccess(settings) {
                OCA.FilesGFTrackDownloads.setting = settings;
                callbackSettings();
            }
        );
    };

    OCA.FilesGFTrackDownloads.FileList = {
        attach: function (fileList) {
            fileList.fileActions.registerAction({
                name: "confirmobject",
                displayName: t("filesgfdownloadactivity", "Confirm"),
                mime: 'all',
                permissions: OC.PERMISSION_READ,
                iconClass: "icon-fgft-confirmation",
                actionHandler: OCA.FilesGFTrackDownloads.MarkAsConfirmed
            });
        }
    };

    var initPage = function () {
        OC.Plugins.register("OCA.Files.FileList", OCA.FilesGFTrackDownloads.FileList);
    };

    $(document).ready(initPage);

})(OCA);

$(document).ready(function() {

    $('.unconfirmed-files .fileid').click(function() {

        var id = $(this).val();

        var data = {
            fileID: id
        };

        $.ajax({
            url: OC.filePath('files_gf_trackdownloads', 'ajax','confirm.php'),
            type: 'POST',
            data: data,
            success: function(element) {

                // parse respone to json format
                var response = JSON.parse(element);

                if (!response.error) {
                    location.reload();
                } else {
                    OC.dialogs.alert(
                        t('filesgfdownloadactivity', response.error_msg),
                        t('filesgfdownloadactivity', 'Error')
                    );
                }
            }
        });
    });
});




var vm = new Vue({
    el: "#app-fgft",
    data: {
        test: []
    },
    mounted: function () {
        //alert('called alert box when app is mounted');
        this.test.push('bilo sta');
        console.log('Test var: ', this.test);
    }
});