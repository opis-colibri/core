<ul class="list-unstyled">
    <?php foreach($items as $index => $item): ?>
    <li>
        <?php if($index <= $current):?>
        <h4 class="text-success">
            <span class="glyphicon glyphicon-ok"></span>
        <?php else:?>
        <h4 class="text-primary">
            <span class="glyphicon glyphicon-chevron-right"></span>
        <?php endif;?>
            <?php print $item;?>
        </h4>
    </li>
    <?php endforeach;?>
</ul>