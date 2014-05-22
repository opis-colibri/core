<div class="module-list">
    <form id="<?php print $form['id'];?>" method="<?php print $form['method'];?>" action="<?php print $form['action'];?>">
        <div class="list-group">
            <?php foreach($list as $item):?>
            <div class="list-group-item">
                <?php print $item; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>