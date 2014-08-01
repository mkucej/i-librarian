<?php
$metadata_to_coins = array(
	'title'=>'rft.atitle',
	'secondary_title'=>'rft.jtitle',
	'journal'=>'rft.stitle',
	'year'=>'rft.date',
	'volume'=>'rft.volume',
	'pages'=>'rft.pages',
	'authors'=>'rft.au'

);

$coins_output = array();

while(list($metadata, $coins) = each($metadata_to_coins)) {

	if ($metadata == 'authors') {
		$authors = array();
		$authors = explode(";",$paper[$metadata]);
		foreach($authors as $input) {
			$output = '';
                        $input = trim($input);
			$input = strtoupper(bin2hex($input));
			$input = str_split($input,2);
			foreach($input as $character) {
				$output.= '%'.$character;
			}
			$coins_output[] = $coins.'='.$output;
		}
	} else {
			$output = '';
			$input = $paper[$metadata];
			if (!empty($input)) {
			$input = strtoupper(bin2hex($input));
			$input = str_split($input,2);
			foreach($input as $character) {
				$output.= '%'.$character;
			}
			$coins_output[] = $coins.'='.$output;
		}
	}
}

$coins_output = join("&amp;",$coins_output);

print PHP_EOL.'<span class="Z3988" title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;';

print $coins_output;

if (!empty($paper['doi'])) print "&amp;rft_id=info:doi/".urlencode($paper['doi']);

print '"></span>'.PHP_EOL;

?>