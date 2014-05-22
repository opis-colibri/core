<div class="container">
    <?php print $jumbotron; ?>
    <?php if(isset($alerts)) print $alerts;?>
    <div class="row">
        <div class="col-md-12">
            <table class="table">
            <?php foreach($messages as $message): ?>
                <tr class="<?php print $message['type'] == 'success' ? 'text-success' : 'text-danger';?>">
                    <td>
                        <span class="fa fa-<?php print $message['type'] == 'success' ? 'check' : 'exclamation';?>-circle"></span>
                    </td>
                    <td>
                        <strong>
                            <abbr title="<?php print $message['attribute']; ?>"><?php print $message['check']; ?></abbr>
                        </strong>
                    </td>
                    <td>
                        <?php print $message['message']; ?>
                    </td>
                </tr>
            <?php endforeach;?>
            </table>
        </div>
    </div>
</div>