<div class="container">
    <?php print $jumbotron; ?>
    <div class="row">
        <div class="col-md-4">
            <h3>Administrator account (optional)</h3>
            <p>
                By default, Opis Colibri modules can be managed using a terminal, so setting up an administrator
                account is not mandatory.
            </p>
            <p>
                If you choose to setup an administrator account, Opis Colibri will provide you
                with a web interface that will allow you to manage your modules directly from a
                web browser.
            </p>
        </div>
        <div class="col-md-8">
            <?php if(isset($alerts)) print $alerts;?>
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
                        <input type="password" name="password" class="form-control" id="db-pass" value="<?php print $password;?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-pass-check" class="col-md-3 control-label text-primary">Retype password</label>
                    <div class="col-md-4">
                        <input type="password" name="check" class="form-control" id="db-pass-check" value="<?php print $check;?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-4">
                        <input type="submit" class="form-control btn btn-primary" value="Save and continue">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>