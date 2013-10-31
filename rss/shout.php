<?php
include '../lib.php' ;
include '../includes/db.php' ;
$query = "SELECT * FROM `shout` ORDER BY `id` ASC" ;
$shouts = query_as_array($query) ;
header('Content-Type: text/xml; charset=iso-8859-1');
echo '<?xml version="1.0" encoding="iso-8859-1" ?>' ;
?>
<rss version="2.0">
	<channel>
		<title>MOGG.fr shouts</title>
		<link>http://mogg.fr</link>
		<description>Shouts on mogg.fr</description>
<?php
foreach ( $shouts as $shout ) {
?>
		<item>
			<guid><?=$shout->id;?></guid>
			<pubDate><?php echo date("D, d M Y H:i:s", strtotime($shout->time)); ?></pubDate>
			<title><?php echo $shout->sender_nick.' : '.$shout->message ; ?></title>
			<link><?=$url;?></link>
			<description><?php echo $shout->sender_nick.' : '.$shout->message ; ?></description>
		</item>
<?php
}
?>
	</channel>
</rss>
