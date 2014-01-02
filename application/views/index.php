<!DOCTYPE html>
<html lang="en" class="no-js">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> 
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
		<title>Blueprint: Vertical Icon Menu</title>
		<meta name="description" content="" />
		<meta name="keywords" content="" />
		<meta name="author" content="Kenneth Muturi - http://ygroup.us" />
		<link rel="shortcut icon" href="../favicon.ico">
		<link rel="stylesheet" type="text/css" href="<?php echo url::site_url('assets/css/default.css'); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo url::site_url('assets/css/component.css'); ?>" />
		<script src="<?php echo url::site_url('assets/js/jquery.js'); ?>"></script>
    	<script src="<?php echo url::site_url('assets/js/modernizr.custom.js'); ?>"></script>
	</head>
	<body>
		<div class="container">
			<header class="clearfix">
				<span>Blueprint</span>
				<h1>Vertical Icon Menu</h1>
				<nav>
					<a href="http://tympanus.net/Blueprints/HorizontalDropDownMenu/" class="icon-arrow-left" data-info="previous Blueprint">Previous Blueprint</a>
					<a href="http://tympanus.net/codrops/?p=14429" class="icon-drop" data-info="back to the Codrops article">back to the Codrops article</a>
				</nav>
			</header>
			<ul class="cbp-vimenu">
				<li><a href="#" class="icon-logo">Logo</a></li>
				<li><a href="#" class="icon-archive">Archive</a></li>
				<li><a href="#" class="icon-search">Search</a></li>
				<li><a href="#" class="icon-pencil">Pencil</a></li>
				<!-- Example for active item:
				<li class="cbp-vicurrent"><a href="#" class="icon-pencil">Pencil</a></li>
				-->
				<li><a href="#" class="icon-location">Location</a></li>
				<li><a href="#" class="icon-images">Images</a></li>
				<li><a href="#" class="icon-download">Download</a></li>
			</ul>
			<div class="main">
				<h2><?php echo $book_chapter->book->name; ?></h2>

				<?php
				foreach($book_chapter as $verse) 
				{
					echo "<p><sup> {$verse->verse} </sup> {$verse->text} </p>";
				}?>			
			</div>
		</div>
	</body>
</html>
