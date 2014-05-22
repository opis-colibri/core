<div class="container">
    <?php print $jumbotron; ?>
    <div class="row">
        <div class="col-md-4">
            <h3>Connection setup (optional)</h3>
            <p>
                Although Opis Colibri supports various databases,
                this prerelease version provides setup support only for MySQL databases.
                If you are not planning to use MySQL databases, or if you are not planning to use databases
                at all, you can skip this step and setup your default database connection later.
            </p>
        </div>
        <div class="col-md-8">
            <?php if(isset($alerts)) print $alerts;?>
            <form class="form-horizontal" role="form" method="post">
                <div class="form-group">
                    <label for="db-name" class="col-md-3 control-label text-primary">Database name</label>
                    <div class="col-md-4">
                        <input type="text" name="database" value="<?php print $database;?>" class="form-control" id="db-name" placeholder="Database name">
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
                    <label for="db-host" class="col-md-3 control-label text-primary">Host</label>
                    <div class="col-md-4">
                        <input type="text" name="host" value="<?php print $host;?>" class="form-control" id="db-host" placeholder="localhost">
                    </div>
                </div>
                <div class="form-group">
                    <label for="db-port" class="col-md-3 control-label text-primary">Port</label>
                    <div class="col-md-4">
                        <input type="text" name="port" value="<?php print $port;?>" class="form-control" id="db-port" placeholder="port">
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