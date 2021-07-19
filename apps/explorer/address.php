<?php
require_once dirname(__DIR__)."/apps.inc.php";
require_once ROOT. '/apps/explorer/include/functions.php';
define("PAGE", true);
define("APP_NAME", "Explorer");

$acc = new Account();

if(isset($_GET['address'])) {
    $address = $_GET['address'];
} else if (isset($_GET['pubkey'])) {
    $pubkey = $_GET['pubkey'];
	$address=Account::getAddress($pubkey);
	$pubkeyCheck = Account::publicKey($address);
	if($pubkeyCheck != $pubkey) {
		header("location: /apps/explorer");
		exit;
    }
}

if(!Account::valid($address)) {
	header("location: /apps/explorer");
	exit;
}


$balance = Account::pendingBalance($address);
$public_key = Account::publicKey($address);

$dm=get_data_model(Account::getCountByAddress($public_key, $address),
    '/apps/explorer/address.php?address='.$address);

$transactions = Account::getTransactions($address, $dm);

$mempool = Account::getMempoolTransactions($address);

?>
<?php
require_once __DIR__. '/../common/include/top.php';
?>

<ol class="breadcrumb m-0 ps-0 h4">
    <li class="breadcrumb-item"><a href="/apps/explorer">Explorer</a></li>
    <li class="breadcrumb-item">Address</li>
    <li class="breadcrumb-item active text-truncate"><?php echo $address ?></li>
</ol>

<table class="table table-sm table-striped">
    <tr>
        <td>Address</td>
        <td><?php echo $address ?></td>
    </tr>
    <tr>
        <td>Public key</td>
        <td><?php echo $public_key ?></td>
    </tr>
    <tr>
        <td class="h4">Balance</td>
        <td class="h4"><?php echo $balance ?></td>
    </tr>
</table>

<?php if(!empty($mempool)) { ?>
    <h4>Mempool transactions</h4>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead class="table-light">
            <tr>
                <th>Id</th>
                <th>Date</th>
                <th>Height</th>
                <th>Block</th>
                <th>Type</th>
                <th>Value</th>
                <th>Fee</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach($mempool as $transaction) { ?>
                <tr>
                    <td><a href="/apps/explorer/tx.php?id=<?php echo $transaction['id'] ?>"><?php echo $transaction['id'] ?></a></td>
                    <td><?php echo display_date($transaction['date']) ?></td>
                    <td><a href="/apps/explorer/block.php?height=<?php echo $transaction['block'] ?>">
							<?php echo $transaction['height'] ?></a></td>
                    <td><a href="/apps/explorer/block.php?height=<?php echo $transaction['block'] ?>">
							<?php echo $transaction['block'] ?></a></td>
                    <td><?php echo $transaction['type'] ?></td>
                    <td><?php echo num($transaction['val']) ?></td>
                    <td><?php echo num($transaction['fee']) ?></td>
                </tr>
			<?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<h4>Transactions</h4>
<div class="table-responsive">
<table class="table table-sm table-striped">
    <thead class="table-light">
        <tr>
            <th>Id</th>
            <th>Date</th>
            <th>Height</th>
            <th>Block</th>
            <th>Type</th>
            <th>Value</th>
            <th>Fee</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($transactions as $transaction) { ?>
            <tr>
                <td><a href="/apps/explorer/tx.php?id=<?php echo $transaction['id'] ?>"><?php echo $transaction['id'] ?></a></td>
                <td><?php echo display_date($transaction['date']) ?></td>
                <td><a href="/apps/explorer/block.php?height=<?php echo $transaction['block'] ?>">
                        <?php echo $transaction['height'] ?></a></td>
                <td><a href="/apps/explorer/block.php?height=<?php echo $transaction['block'] ?>">
                        <?php echo $transaction['block'] ?></a></td>
                <td><?php echo $transaction['type'] ?></td>
                <td><?php echo num($transaction['val']) ?></td>
                <td><?php echo num($transaction['fee']) ?></td>
            </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php echo $dm['paginator'] ?>
<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
