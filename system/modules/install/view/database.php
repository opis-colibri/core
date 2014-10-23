<div class="container">
    <?php print $jumbotron; ?>
    <div class="row">
        <div class="col-md-4">
            <h3>Connection setup (optional)</h3>
            <p>
                Define a default database connection for your web application.
                You can skip this step if you are not planning to use databases within your application.
            </p>
        </div>
        <div class="col-md-8">
            <?php if(isset($alerts)) print $alerts;?>
            <form class="form-horizontal" role="form" method="post">
                <div class="form-group">
                    <label for="db-name" class="col-md-3 control-label text-primary">Connection DSN</label>
                    <div class="col-md-4">
                        <input type="text" name="dsn" value="<?php print $dsn;?>" class="form-control" id="db-name" placeholder="mysql:host=localhost;dbname=mydatabase">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-user" class="col-md-3 control-label text-primary">Database username</label>
                    <div class="col-md-4">
                        <input type="text" name="username" value="<?php print $username;?>" class="form-control" id="db-user" placeholder="User name">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-pass" class="col-md-3 control-label text-primary">Database password</label>
                    <div class="col-md-4">
                        <input type="password" name="password" value="<?php print $password;?>" class="form-control" id="db-pass">
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