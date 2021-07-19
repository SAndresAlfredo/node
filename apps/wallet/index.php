<?php
require_once dirname(__DIR__)."/apps.inc.php";
require_once ROOT. '/apps/explorer/include/functions.php';
define("PAGE", true);
define("APP_NAME", "Wallet");
session_start();

if(isset($_POST['action'])) {
	$action = $_POST['action'];
	if($action == "send") {
		$dst = trim($_POST['dst']);
		if(strlen($dst)==0 || !Account::valid($dst)) {
			$_SESSION['msg']=[['icon'=>'error', 'text'=>'Invalid or empty address']];
			header("location: /apps/wallet/index.php");
			exit;
        }
		$amount = floatval($_POST['amount']);
		$fee = $_POST['fee'];
		if(empty($amount)) {
			$_SESSION['msg']=[['icon'=>'error', 'text'=>'Invalid amount']];
			header("location: /apps/wallet/index.php");
			exit;
        }
		$acc = new Account();
		$public_key = $_POST['public_key'];
		$address = Account::getAddress($public_key);
		$balance = Account::pendingBalance($address);
		$total=$amount+$fee;
		$val=num($amount);
		$fee=num($fee);
		if ($balance<$total) {
			$_SESSION['msg']=[['icon'=>'error', 'text'=>'Not enough funds in balance']];
			header("location: /apps/wallet/index.php");
			exit;
		}
		$date = $_POST['date'];
		$msg = $_POST['msg'];
		$info=$val."-".$fee."-".$dst."-".$msg."-".TX_TYPE_SEND."-".$public_key."-".$date;
		$signature = $_POST['signature'];
		$verify = Account::checkSignature($info, $signature, $public_key);
		if($verify) {

			$transaction = [
				"val"        => $val,
				"fee"        => $fee,
				"dst"        => $dst,
				"public_key" => $public_key,
				"date"       => $date,
				"type"    => TX_TYPE_SEND,
				"message"    => $msg,
				"signature"  => $signature,
			];
            $hash = Transaction::addToMemPool($transaction, $public_key, $error);
            if($hash === false) {
	            $_SESSION['msg']=[['icon'=>'error', 'text'=>'Transaction can not be sent: '.$error]];
	            header("location: /apps/wallet/index.php");
	            exit;
            } else {
	            $_SESSION['msg']=[['icon'=>'success', 'text'=>'Transaction sent! Id of transaction: '.$hash]];
	            header("location: /apps/wallet/index.php");
	            exit;
            }


        } else {
			$_SESSION['msg']=[['icon'=>'error', 'msg'=>'Transaction can not be sent: Signature not verified']];
			header("location: /apps/wallet/index.php");
			exit;
        }
    }
}

if(isset($_GET['action'])) {
    $action = $_GET['action'];
    if($action == 'logout') {
        unset($_SESSION['public_key']);
        session_destroy();
        header("location: /apps/wallet");
        exit;
    }
}

$loggedIn = false;
if(isset($_SESSION['public_key'])) {
	$loggedIn = true;
	$public_key = $_SESSION['public_key'];
	$acc = new Account();
	$address = Account::getAddress($public_key);
	$balance = Account::pendingBalance($address);
	$total = Account::getCountByAddress($public_key, $address);
	$dm = get_data_model($total, "/apps/wallet/index.php?");
	$transactions = Transaction::getWalletTransactions($address, $dm);
}
if(!$loggedIn) {
    header("location: /apps/wallet/login.php");
    exit;
}
$acc=new Account();
?>
<?php
require_once __DIR__. '/../common/include/top.php';
?>

    <div class="row h4">
        <div class="col-sm-2">Address</div>
        <div class="col-sm-8" style="word-break: break-all">
            <?php echo $address ?>
        </div>
        <div class="col-sm-2 text-end">
            <a href="/apps/wallet/?action=logout" class="btn btn-outline-primary">Logout</a>
        </div>
    </div>
    <div class="row h4" id="private-key-row">
        <div class="col-sm-2">Private key</div>
        <div class="col-sm-8">
            <div class="input-group auth-pass-inputgroup">
                <input type="password" class="form-control" aria-label="Password" value=""
                       aria-describedby="password-addon" id="private_key" name="private_key" required="required"/>
                <button class="btn btn-light shadow-none ms-0" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
            </div>
        </div>
    </div>
    <hr/>
    <div class="row h4">
        <div class="col-sm-2">Balance</div>
        <div class="col-sm-2"><?php echo $balance ?></div>
        <div class="col-sm-8 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendModal">
                Send
            </button>
        </div>
    </div>
    <hr/>

    <?php if ($transactions['mempool']) { ?>

        <h4>Pending transactions</h4>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Height</th>
                    <th>Id</th>
                    <th>Block</th>
                    <th>Type</th>
                    <th>Src</th>
                    <th>Dst</th>
                    <th>Value</th>
                </tr>
                </thead>
                <tbody>
			    <?php foreach ($transactions['mempool'] as $transaction) { ?>
                    <tr>
                        <td><?php echo display_date($transaction['date']) ?></td>
                        <td><?php echo $transaction['height'] ?></td>
                        <td><?php echo explorer_tx_link($transaction['id'], true) ?></td>
                        <td><?php echo explorer_block_link($transaction['block'], true) ?></td>
                        <td><?php echo $transaction['type'] ?></td>
                        <td><?php echo explorer_address_link(Account::getAddress($transaction['public_key'])) ?></td>
                        <td><?php echo explorer_address_link($transaction['dst']) ?></td>
                        <td><?php echo num($transaction['val']) ?></td>
                    </tr>
			    <?php } ?>
                </tbody>
            </table>
        </div>

    <?php } ?>

    <h4>Completed transactions</h4>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Height</th>
                    <th>Id</th>
                    <th>Block</th>
                    <th>Type</th>
                    <th>Src</th>
                    <th>Dst</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions['completed'] as $transaction) { ?>
                    <tr>
                        <td><?php echo display_date($transaction['date']) ?></td>
                        <td><?php echo $transaction['height'] ?></td>
                        <td><?php echo explorer_tx_link($transaction['id'], true) ?></td>
                        <td><?php echo explorer_block_link($transaction['block'], true) ?></td>
                        <td><?php echo $transaction['type'] ?></td>
                        <td><?php echo explorer_address_link(Account::getAddress($transaction['public_key'])) ?></td>
                        <td><?php echo explorer_address_link($transaction['dst']) ?></td>
                        <td><?php echo num($transaction['val']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php echo $dm['paginator'] ?>


    <div class="modal fade" id="sendModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="sendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">Send <?php echo COIN_NAME ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <div>
                            <label class="form-label">Receiver address:</label>
                            <input type="text" name="dst" id="dst" value="" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">
                                Amount:
                            </label>
                            <div class="float-end text-muted">Available:
                                <a href="#" onclick="setAmount(this); return false;"><?php echo $balance ?></a>
                            </div>
                            <input type="text" id="amount" name="amount" value="" class="form-control" required/>
                            <input type="hidden" name="action" value="send"/>
                            <input type="hidden" name="signature" id="signature" value=""/>
                            <input type="hidden" name="public_key" id="public_key" value="<?php echo $public_key ?>"/>
                            <input type="hidden" name="fee" id="fee" value="<?php echo TX_FEE ?>"/>
                            <input type="hidden" name="date" id="date" value=""/>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">
                                Private key:
                            </label>
                            <input type="password" id="private_key" value="" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">
                                Message (optional):
                            </label>
                            <input type="text" id="msg" name="msg" value="" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="processSend()">Send</button>
                </div>
            </div>
        </div>
    </div>

<script src="/apps/miner/js/web-miner.js" type="text/javascript"></script>
<script type="text/javascript">
    function setAmount(el) {
        $("#amount").val($(el).html());
    }
    function processSend() {
        try {
            let privateKey = $("#private_key").val().trim()
            let amount = Number($("#amount").val()).toFixed(8);
            let fee = Number($("#fee").val()).toFixed(8);
            let dst = $("#dst").val()
            let msg = $("#msg").val()
            let date = Math.round(new Date().getTime()/1000)
            let data = amount + '-' + fee + '-' + dst + '-' + msg + '-' + '<?php echo TX_TYPE_SEND ?>' + '-'
                + '<?php echo $public_key ?>' + '-' + date
            let sig = sign(data, privateKey)
            $("#signature").val(sig)
            $("#date").val(date)
            $("form").submit()
        } catch (e) {
            console.error(e)
            Swal.fire(
                {
                    title: 'Can not sign login form!',
                    text: 'Please check you private key',
                    icon: 'error'
                }
            )
        }
    }
</script>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
<script type="text/javascript">
    $(function(){
        $('#sendModal').on('show.bs.modal', function (e) {
            if(localStorage.getItem('privateKey')) {
                $("#private_key").val(localStorage.getItem('privateKey'))
            }
        })

        $("#password-addon").on('click', function () {
            if ($(this).siblings('input').length > 0) {
                $(this).siblings('input').attr('type') == "password" ? $(this).siblings('input').attr('type', 'input') : $(this).siblings('input').attr('type', 'password');
            }
        })

        if(localStorage.getItem('privateKey')) {
            $("#private_key").val(localStorage.getItem('privateKey'))
            $("#private-key-row").show()
        }
    })
</script>
