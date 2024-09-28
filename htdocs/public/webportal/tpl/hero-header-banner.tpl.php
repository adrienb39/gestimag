<!-- file hero-header-banner.tpl.php -->
<section class="hero-header" <?php echo !empty($context->theme->bannerUseDarkTheme) ? ' data-theme="dark" ': '' ?> >
	<div class="container">
		<h1 class="hero-header__title"><?php echo $context->title; ?></h1>
		<div class="hero-header__desc"><?php echo $context->desc; ?></div>
	</div>
</section>
