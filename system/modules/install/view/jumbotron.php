<div class="jumbotron">
    <div class="row">
        <div class="col-md-4">
            <img class="img-responsive" src="<?php print $logo;?>">  
        </div>
        <div class="col-md-8">
            <h2><strong><?php print $title;?></strong></h2>
            <p class="lead"><?php print $description;?></p>
            <?php if(!isset($button['link'])): ?>
            <form role="form" method="post">
                <div class="text-center">
                    <input type="submit" class="btn btn-lg btn-primary text-center" value="<?php print $button['text'];?>">
                </div>
            </form>
            <?php else: ?>
            <p class="text-center">
                <a class="btn btn-lg btn-primary" role="button" href="<?php print $button['link'];?>"><?php print $button['text'];?></a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>