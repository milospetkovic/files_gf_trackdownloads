<?php
$activeRouteClass = 'svg active';
?>
<ul>
	<li class="sprite"><a class="icon <?php if ($_['active_route'] == 'index') { p($activeRouteClass); } ?> icon-fgft-confirmation" href="/apps/files_gf_trackdownloads/"><?php p($l->t('Shared with you and unconfirmed')) ?> (<?php p($_['count_files']['index']) ?>)</a></li>
	<li class="sprite"><a class="icon <?php if ($_['active_route'] == 'yourconfirmedfiles') { p($activeRouteClass); } ?> icon-fgft-confirmation" href="/apps/files_gf_trackdownloads/yourconfirmedfiles"><?php p($l->t('Shared with you and confirmed')) ?> (<?php p($_['count_files']['yourconfirmedfiles']) ?>)</a></li>
	<li class="sprite"><a class="icon <?php if ($_['active_route'] == 'yoursharednotconfirmed') { p($activeRouteClass); } ?> icon-fgft-confirmation" href="/apps/files_gf_trackdownloads/yoursharednotconfirmed"><?php p($l->t('Shared with others and unconfirmed')) ?> (<?php p($_['count_files']['yoursharednotconfirmed']) ?>)</a></li>
	<li class="sprite"><a class="icon <?php if ($_['active_route'] == 'yoursharedandconfirmed') { p($activeRouteClass); } ?> icon-fgft-confirmation" href="/apps/files_gf_trackdownloads/yoursharedandconfirmed"><?php p($l->t('Shared with others and confirmed')) ?> (<?php p($_['count_files']['yoursharedandconfirmed']) ?>)</a></li>
    <?php
    /*
	<li>
		<a href="#">First level container</a>
		<ul>
			<li><a href="#">Second level entry</a></li>
			<li><a href="#">Second level entry</a></li>
		</ul>
	</li>
    */
    ?>
</ul>
