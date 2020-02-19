<?php
//OC_Util::addHeader('meta', array('http-equiv' => 'Content-Security-Policy', 'content'=>"default-src *; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://www.google.com"));

//script('files_gf_trackdownloads', 'vue/dist/vue');
//script('files_gf_trackdownloads', 'script');
//script('files_gf_trackdownloads', 'main');
//script('files_gf_trackdownloads', 'vueexample');
script('files_gf_trackdownloads', 'build/test');
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
			<?php print_unescaped($this->inc('content/index')); ?>
		</div>
	</div>
</div>

