<?php
include '../lib.php' ;
include '../includes/db.php' ;
$query = "SELECT * FROM `shout` ORDER BY `id` DESC LIMIT 0, 20" ;
$shouts = query_as_array($query) ;
header('Content-Type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8" ?>' ;
?>
<rss version="2.0">
	<channel>
		<title>MOGG.fr shouts</title>
		<link>http://mogg.fr</link>
		<description>Shouts on mogg.fr</description>
<?php
foreach ( $shouts as $shout ) {
	$display = htmlspecialchars($shout->sender_nick).' : '.htmlspecialchars($shout->message) ;
?>
		<item>
			<guid><?=$shout->id;?></guid>
			<pubDate><?php echo date("D, d M Y H:i:s", strtotime($shout->time)); ?></pubDate>
			<title><?=$display;?></title>
			<link></link>
			<description><?=$display;?></description>
		</item>
<?php
}
?>
	</channel>
</rss>
