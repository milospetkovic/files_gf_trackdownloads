/**
 *
 * (c) Copyright Ascensio System SIA 2020
 *
 * This program is a free software product.
 * You can redistribute it and/or modify it under the terms of the GNU Affero General Public License
 * (AGPL) version 3 as published by the Free Software Foundation.
 * In accordance with Section 7(a) of the GNU AGPL its Section 15 shall be amended to the effect
 * that Ascensio System SIA expressly excludes the warranty of non-infringement of any third-party rights.
 *
 * This program is distributed WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * For details, see the GNU AGPL at: http://www.gnu.org/licenses/agpl-3.0.html
 *
 * You can contact Ascensio System SIA at 20A-12 Ernesta Birznieka-Upisha street, Riga, Latvia, EU, LV-1050.
 *
 * The interactive user interfaces in modified source and object code versions of the Program
 * must display Appropriate Legal Notices, as required under Section 5 of the GNU AGPL version 3.
 *
 * Pursuant to Section 7(b) of the License you must retain the original Product logo when distributing the program.
 * Pursuant to Section 7(e) we decline to grant you any rights under trademark law for use of our trademarks.
 *
 * All the Product's GUI elements, including illustrations and icon sets, as well as technical
 * writing content are licensed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International.
 * See the License terms at http://creativecommons.org/licenses/by-sa/4.0/legalcode
 *
 */

(function (OCA) {

    OCA.FilesGFTrackDownloads = _.extend({
        AppName: "files_gf_trackdownloads",
        context: null,
        folderUrl: null
    }, OCA.FilesGFTrackDownloads);


    OCA.FilesGFTrackDownloads.MarkAsConfirmed = function (fileName, context) {

        // console.log('ime fajla koji pozivam:', fileName);
        // console.log('context koji pozivam:', context);
        //var dir = context.dir || context.fileList.getCurrentDirectory();
        //var isDir = context.$file.attr('data-type') === 'dir';
        //var url = context.fileList.getDownloadUrl(fileName, dir, isDir);
        //console.log(dir, isDir);

        var data = {
            nameOfFile: fileName
        };

        $.ajax({
            url: OC.filePath('files_gf_trackdownloads', 'ajax','confirm.php'),
            type: 'POST',
            //contentType: 'application/json',
            data: data,
            success: function(element) {

                console.log('vracenoooo');
                element = element.replace(/null/g, '');
                response = JSON.parse(element);
                if(response.code == 1){
                    context.fileList.reload();
                }else{
                    context.fileList.showFileBusyState(tr, false);
                    OC.dialogs.alert(
                        t('extract', response.desc),
                        t('extract', 'Error extracting '+filename)
                    );
                }
            }
        });

        // $.get( OC.filePath('files_gf_trackdownloads', 'ajax', action + '.php'), {}, function(result) {
        //     console.log('Ovo je rezultat: ', result);
        //
        //     if (result && result.status == 'success') {
        //         OC.dialogs.alert('SUCCESS ', t('files_gf_trackdownloads', 'SUCCESS Title'));
        //
        //     } else {
        //         // show error message
        //         //result.data.message
        //         OC.dialogs.alert('ERROR! ', t('files_gf_trackdownloads', ' ERROR Title'));
        //     }
        // });



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

            var register = function() {
                fileList.fileActions.registerAction({
                    name: "confirmobject",
                    displayName: t(OCA.FilesGFTrackDownloads.AppName, "Confirm"),
                    mime: 'all',
                    permissions: OC.PERMISSION_READ,
                    iconClass: "icon-fgft-confirmation",
                    actionHandler: OCA.FilesGFTrackDownloads.MarkAsConfirmed
                });
            }

            OCA.FilesGFTrackDownloads.GetSettings(register);
        }
    };

    var initPage = function () {
        OC.Plugins.register("OCA.Files.FileList", OCA.FilesGFTrackDownloads.FileList);
    };

    $(document).ready(initPage);

})(OCA);