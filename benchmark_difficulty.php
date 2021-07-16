<?php 
include('hashcash_server.php');

// Figure out approximately how many hashes need to be generated
// in order to meet a given difficulty target
for ($difficulty = 1; $difficulty <= 30; $difficulty++) {
	$stamp = hc_HashFunc(uniqid() . "127.0.0.1" . "hu867q43gafy93yq794u");
	$nonce = 0;
	while (true) {
		$work = hc_HashFunc($stamp . $nonce);

		$leadingBits = hc_ExtractBits($work, $difficulty);

		// if the leading bits are all 0, the difficulty target was met
		if (strlen($leadingBits) > 0 && intval($leadingBits) == 0) {
			echo "Difficulty: $difficulty\tNonce: $nonce\n";
			break;
		}

		$nonce++;
	}
}

?>