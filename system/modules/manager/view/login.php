<div class="container">
    <?php if(isset($alerts)) print $alerts; ?>
    <div class="center-block login-block">
        
        <form class="form-horizontal" role="form" method="post">
            <div class="form-group">
                <label for="db-user" class="col-md-4 control-label text-primary">Username</label>
                <div class="col-md-8">
                    <input type="text" name="username" class="form-control" id="db-user" value="<?php print $username;?>" placeholder="Username">
                </div>
            </div>
            <div class="form-group">
                <label for="db-pass" class="col-md-4 control-label text-primary">Password</label>
                <div class="col-md-8">
                    <input type="password" name="password" class="form-control" id="db-pass" value="<?php print $password;?>" placeholder="Password">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-4 col-md-8">
                    <input type="submit" class="form-control btn btn-primary" value="Login">
                </div>
            </div>
        </form>
    </div>
</div>