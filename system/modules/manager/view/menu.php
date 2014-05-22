<div class="pull-right">
    <form method="post" action="<?php print $collect['action'];?>">
        <?php if(isset($links)): ?>
        <?php foreach($links as $text => $link): ?>
            <a title="<?php print $link['title'];?>" href="<?php print $link['href'];?>" class="btn btn-primary">
                <span class="<?php print $link['class'];?>"></span> <?php print $text; ?>
            </a>
        <?php endforeach; ?>
        <?php endif;?>
        <?php if(isset($collect)): ?>
        <input type="submit" class="btn btn-primary" name="action" value="<?php print $collect['title'];?>">
        <?php endif; ?>
        <?php if(Session()->get('is_system_admin', false)): ?>
        <input type="submit" class="btn btn-success" name="action" value="Logout">
        <?php endif;?>
    </form>
</div>