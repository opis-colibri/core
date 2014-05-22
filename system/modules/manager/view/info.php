<span class="badge <?php print $statusClass; ?>"><?php print $status; ?></span>
<h4>
    <span class="text-primary"><?php print $title;?></span> <small><?php print $name;?></small></h4>
<p>
    <?php print $description; ?>
</p>
<?php if(!empty($dependencies)): ?>
<p class="dependencies">
    <strong>Dependencies: </strong>
    <?php foreach($dependencies as $name => $info): ?>
     <span class="<?php print $info['class'];?> dependency"><?php print $name;?> (<?php print $info['status'];?>)</span>
    <?php endforeach; ?>
</p>
<?php endif; ?>
<?php if(!empty($dependents)): ?>
<p class="dependents">
    <strong>Required by: </strong>
    <?php foreach($dependents as $name => $info): ?>
     <span class="<?php print $info['class'];?> dependent"><?php print $name;?> (<?php print $info['status'];?>)</span>
    <?php endforeach; ?>
</p>
<?php endif; ?>
<div>
    <?php foreach($buttons as $button): ?>
    <input type="submit" <?php if($button['disabled']) print 'disabled="disabled" ';?>class="btn <?php print $button['class'];?>" name="<?php print $button['name'];?>" value="<?php print $button['value'];?>">
    <?php endforeach; ?>
</div>