<!DOCTYPE html>
<html lang="en-GB">
	<head>
		<meta charset="utf-8">
		<title>MQ</title>
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<base href="/">
		<link href='https://fonts.googleapis.com/css?family=Lato:400,900' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="/css/normalize.css" type="text/css" media="all" />
		<link rel="stylesheet" href="/css/moviequotes.css" type="text/css" media="all" />
	</head>
	<body>
		<header>
			<h1><a href="/"><span class="m">M</span>Q</a></h1>
			<nav><a href="/">list all quotes</a> <a href="/quotes/random">random quote</a> <a href="/download/all">download all quotes</a></nav>
			<?php
if (isset($error)) {
?><div class="error"><?php echo $error; ?></div><?php
}
?>
		</header>
