<div class="container">
    <div class="row">
        <div class="col-md-3">
            <h4>Administrator account</h4>
            <p>
                Create or update your administrator account.
            </p>
        </div>
        <div class="col-md-9">
            <?php if(isset($alerts)) print $alerts; ?>
            <form class="form-horizontal" role="form" method="post">
                <div class="form-group">
                    <label for="db-user" class="col-md-3 control-label text-primary">Username</label>
                    <div class="col-md-4">
                        <input type="text" name="username" class="form-control" id="db-user" value="<?php print $username;?>" placeholder="Username">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-pass" class="col-md-3 control-label text-primary">Password</label>
                    <div class="col-md-4">
                        <input type="password" name="password" class="form-control" id="db-pass" value="<?php print $password;?>" placeholder="Password">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-pass-check" class="col-md-3 control-label text-primary">Retype password</label>
                    <div class="col-md-4">
                        <input type="password" name="check" class="form-control" id="db-pass-check" value="<?php print $check;?>" placeholder="Verify password">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-4">
                        <input type="submit" class="form-control btn btn-primary" value="Create account">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>