<div class="app-content-detail">
    <div id="container" class="container unconfirmed-files">
        <div class="section unconfirmed-files-section group">

            <h2><?php p($l->t('Your unconfirmed files')) ?></h2>

        <?php
        if (count($_['data'])) {
        ?>
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php p($l->t('Shared by user')) ?></th>
                        <th><?php p($l->t('Shared date')) ?></th>
                        <th><?php p($l->t('Confirm until')) ?></th>
                        <th><?php p($l->t('File')) ?></th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach($_['data'] as $ind => $data) { ?>
                    <tr>
                        <th scope="row">
                            <input type="checkbox" name="fileid[]" class="fileid" value="<?php echo $data['fileid'] ?>" />
                        </th>
                        <td><?php echo $data['uid_initiator'] ?></td>
                        <td><?php echo \OCA\FilesGFTrackDownloads\Util\DateTimeUtility::convertTimestampToUserFriendlyDateTime($data['stime']) ?></td>
                        <td><?php echo \OCA\FilesGFTrackDownloads\Util\DateTimeUtility::convertDateTimeToUserFriendlyDate($data['expiration']) ?></td>
                        <td><?php echo $data['file_target'] ?></td>
                    </tr>
                <?php } ?>
                </tbody>

            </table>

        <?php } else { ?>

             <div class="text-warning">
                <?php p($l->t('No results')) ?>
             </div>

        <?php } ?>

        </div>
    </div>
</div>
