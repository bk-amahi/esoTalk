<?php echo "<?xml version='1.0' encoding='{$language["charset"]}'?>\n"; ?>
<rss version='2.0'>
	<channel>
		<title><?php echo $this->controller->title; ?></title>
		<link><?php echo sanitize($this->controller->link); ?></link>
		<description><?php echo $this->controller->description; ?></description>
		<pubDate><?php echo $this->controller->pubDate; ?></pubDate>
		<generator>esoTalk</generator>
<?php foreach ($this->controller->items as $item): ?>
		<item>
			<title><?php echo $item["title"]; ?></title>
			<link><?php echo sanitize($item["link"]); ?></link>
			<description><?php echo $item["description"]; ?></description>
			<pubDate><?php echo $item["date"]; ?></pubDate>
			<guid><?php echo sanitize($item["link"]); ?></guid>
		</item>
<?php endforeach; ?>
	</channel>
</rss>