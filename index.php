<?php

$site_name = "👨🏻‍💻 Aubrey's Notes";

header( 'Content-Type: text/html; charset=utf-8' );
mb_internal_encoding( "UTF-8" );

$post = get_post_name( $_GET['post'] ?? '', 'de-permalink' );

if ( ! empty( $post ) ) {
	show_post( $post );
} else {
	show_posts();
}

function get_post_name( $file, $type = 'basename' ) {

	$base = basename( str_replace( '.html', '', $file ) );

	if ( 'permalink' === $type ) {
		return str_replace( ' ', '-', $base );
	}

	if ( 'de-permalink' === $type ) {
		return str_replace( '-', ' ', $base );
	}

	return $base;
}

function get_post_files() {

	$files = glob( __DIR__ . '/html/*.html' );

	$sorted_files = array();

	foreach ( $files as $file ) {
		$sorted_files[ filemtime( $file ) ] = $file;
	}

	krsort( $sorted_files );

	return $sorted_files;
}

function get_note_title($filePath) {
	if (!file_exists($filePath)) {
			return false;
	}

	// Open the file for reading
	$handle = fopen($filePath, 'r');
	if ($handle) {
			// Read the first non-empty line
			while (($line = fgets($handle)) !== false) {
					$line = trim($line); // Trim whitespace
					if (!empty($line)) {
							fclose($handle);
							return strip_tags($line); // Strip any HTML tags and return
					}
			}
			fclose($handle);
	}

	return false;
}

function show_posts() {

	?>

	<?php the_header( 'Posts' ); ?>

	<div class="posts">
		<ul class="post-list">

				<?php foreach ( get_post_files() as $date => $file ) : ?>

					<li>
						<a href="?post=<?php echo get_post_name( $file, 'permalink' ); ?>"><?php echo get_note_title( $file ); ?></a><br>
						<small><span class="date"><date><?php echo post_date( get_post_meta( 'created_date', get_post_contents( get_post_name( $file ) ) ) ); ?></date></span></small>
					</li>

				<?php endforeach; ?>

			</ul>
	</div>

	<?php footer(); ?>

	<?php
}

function the_header( $title ) {
	
	global $site_name;
	
	?>

	<!DOCTYPE html>
		<html lang="en" data-theme="light">
		<head>
			<meta charset="UTF-16">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			
			<title><?php echo $title; ?></title>
			
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">

			<meta name="color-scheme" content="light dark" />

			<link
				rel="stylesheet"
				href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.classless.blue.min.css"
			/>

			<style>

				body {
					zoom: 95%;
				}

				.post > div,
				.post div > div {
					margin-bottom: var(--pico-typography-spacing-vertical);
				}
				
				.site-title a {
					text-decoration: none;
					color: black;
				}

				.site-title {
					font-size: 80%;
					border-bottom: 1px solid #dadada;
					padding-bottom: 30px;
					padding-top: 30px;
				}

				.post-date {
					margin-top: 30px;
					padding-top: 30px;
					border-top: 1px solid #dadada;
				}

				code {
					display:block;
					padding-left: 20px;
					background: none;
				}

				.post h1.post-title {
					margin-top: 20px;
				}

				.post > div {
					margin-bottom: var(--pico-typography-spacing-vertical);
				}

				img {
					border-radius: 5px;
				}

				.post-list {
					padding-left: 0;
				}

				.post-list li {
					list-style: none;
					padding-bottom: 5px;
				}

			</style>

		</head>
		<body>

		<main  class="container">

			<header><h1 class="site-title"><a href="../"><?php echo $site_name; ?></a></h1></header>

	<?php
}

function footer() {
	?>

		</main>

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
		<script>hljs.highlightAll();</script>
		
		</body>
	</html>

	<?php
}

function get_post_filename( $post ) {
	return __DIR__ . '/html/' . "{$post}.html";
}

function pre_wrap( $html ) {

	return str_replace(
		[
			"<div><br></div>\n<div><tt>",
			"</tt></div>\n<div><br></div>"
		],
		[
			"\n<div><pre><code>",
			"</tt></div></code></pre>\n",
		],
		$html
	);
}

function remove_code( $html ) {
	return str_replace(
		[
			'<div><tt>',
			'</tt></div>',
			'<tt>',
			'</tt>'
		],
		[
			'',
			'',
			'',
			'',
		],
		$html
	);
}

function remove_extra_breaks( $html ) {
	return str_replace(
		[
			'<div><b><br></b></div>',
			'<div><br></div>',
		],
		[
			'',
			'',
		],
		$html
	);
}

function remove_brs_in_code( $html ) {
	$dom = new DOMDocument();

	// Ensure UTF-8 encoding
	libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();

	// Get all <code> elements
	$codeTags = $dom->getElementsByTagName('code');

	// Iterate over each <code> tag and remove <br> elements
	foreach ($codeTags as $code) {
			$brTags = $code->getElementsByTagName('br');
			while ($brTags->length > 0) {
					$brTags->item(0)->parentNode->removeChild( $brTags->item(0) );
			}
	}

	// Save the modified HTML
	$result = $dom->saveHTML();

	return $result;
}

function prepare_post( $post ) {

	return remove_brs_in_code(
		remove_extra_breaks(
			remove_code(
				str_replace(
					array(
						"\t", // Tabs in code
						'&lt',
						'&gt',
					),
					array(
						'&nbsp;&nbsp;', // Tabs in code
						'&lt;',
						'&gt;',
					),
					pre_wrap(
						get_post_contents( $post )
					)
				)
			)
		)
	);
}

function get_post_contents( $post ) {
	return file_get_contents( get_post_filename( $post ) );
}

function get_post_meta( $meta, $html ) {
	
	$pattern = "/<!--\s*{$meta}:\s*([^>]+?)\s*-->/";

	preg_match($pattern, $html, $matches);
	
	return trim($matches[1] ?? 'Unknown');
}

function post_date( $date ) {
	return DateTime::createFromFormat('l, F j, Y \a\t h:i:s A', $date)->format('F j, Y');
}

function show_post( $post ) {

	if ( ! file_exists( get_post_filename( $post ) ) ) {
		
		show_posts();
		return;
	}

	ob_start();

	?>

			<?php the_header( $post ); ?>

			<div class="post">
				
				<?php echo prepare_post( $post ); ?>

				<p class="post-date">
					<strong>Updated on: </strong>
					<date><?php echo post_date( get_post_meta( 'updated_date', get_post_contents( $post ) ) ); ?></date>
				</p>

			</div>

			<?php footer(); ?>
	<?php

	echo ob_get_clean();
}

?>