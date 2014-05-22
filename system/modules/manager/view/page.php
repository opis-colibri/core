<div id="header" class="container">
    <?php if(isset($menu)) print $menu; ?>
    <h3 class="text-muted"><?php print $title;?></h3>
</div>
<?php print $content; ?>
<div id="footer">
    <p class="text-center text-muted" style="margin-top: 10px;">
       <strong>Powered by</strong> <img src="<?php print $logo; ?>">
    </p>
</div>
