<?php
style('files_gf_trackdownloads', 'style');
style('files_gf_trackdownloads', 'bootstrap/bootstrap.edit');
?>

<div id="app-fgft">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('navigation/index')); ?>
        <?php print_unescaped($this->inc('settings/index')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">
            <?php print_unescaped($this->inc('content/yourconfirmedfiles')); ?>
        </div>
    </div>
</div>

