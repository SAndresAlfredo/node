<?php
//Local mode miner
define("MINER_RUN", true);
global $_config;
if(php_sapi_name() !== 'cli') exit;
require_once dirname(__DIR__).'/include/init.inc.php';
$block = new Block();
$acc = new Account();

define("MINER_LOCK_PATH", ROOT.'/tmp/miner-lock');
_log("Running miner cli", 0);
// make sure there's only a single miner process running at the same time
if (file_exists(MINER_LOCK_PATH) && !DEVELOPMENT) {
	_log("Miner lock in place: " .MINER_LOCK_PATH);
	die("Miner lock in place".PHP_EOL);
}

$peers = Peer::getCount(true);
if(empty($peers) && false) {
	_log("No peers for miner");
	exit;
}


_log("Starting miner");


if(!$_config['miner'] && !DEVELOPMENT) {
	_log("Miner not enabled");
	exit;
}

if(!$_config['miner_public_key']) {
	_log("Miner public key not defined");
	exit;
}
if(!$_config['miner_private_key']) {
	_log("Miner private key not defined");
	exit;
}

_log("Writing lock file", 0);
$lock = fopen(MINER_LOCK_PATH, "w");
fclose($lock);

$miner = new NodeMiner();

$res = $miner->start();
if($res === false) {
	_log("Miner failed to start",0);
	@unlink(MINER_LOCK_PATH);
	exit;
}

@unlink(MINER_LOCK_PATH);
_log("Miner stopped", 0);
