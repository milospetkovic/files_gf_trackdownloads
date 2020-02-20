<?php
use OCA\FilesGFTrackDownloads\Util\DateTimeUtility;
use OCA\FilesGFTrackDownloads\Util\LinkToObjectUtility;
?>

<div class="app-content-detail">
    <div id="container" class="container-fluid unconfirmed-files">
        <div class="section unconfirmed-files-section group">
            <div class="row">
                <div class="col-xs-12">

                    <h2><?php p($l->t('Shared with others and not confirmed')) ?></h2>

                    <?php
                    if (count($_['data'])) {
                        ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm table-unconfirmed-files">
                                <thead>
                                <tr>
                                    <th><?php p($l->t('Shared to user')) ?></th>
                                    <th><?php p($l->t('Shared date')) ?></th>
                                    <th><?php p($l->t('Confirm until')) ?></th>
                                    <th><?php p($l->t('File')) ?></th>
                                </tr>
                                </thead>

                                <tbody>
                                <?php foreach($_['data'] as $ind => $data) { ?>
                                    <tr>
                                        <td><?php echo $data['share_with'] ?></td>
                                        <td><?php echo DateTimeUtility::convertTimestampToUserFriendlyDateTime($data['stime']) ?></td>
                                        <td><?php echo DateTimeUtility::convertDateTimeToUserFriendlyDate($data['expiration']) ?></td>
                                        <td><?php print_unescaped(LinkToObjectUtility::returnLinkToTheFile($data['fileid'], ltrim($data['file_target'], '/'))) ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>

                            </table>
                        </div>

                    <?php } else { ?>

                        <div class="text-warning">
                            <?php p($l->t('No results')) ?>
                        </div>

                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>
