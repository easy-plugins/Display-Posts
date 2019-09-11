<?php
/**
 * @var \Easy_Plugins\Display_Posts\Template\Post\Partials $partial
 * @var string $tag
 * @var array  $class
 * @var string $image
 * @var string $title
 * @var string $date
 * @var string $author
 * @var string $terms
 * @var string $excerpt
 * @var string $content
 */
$classes = implode( ' ', $class );

echo "<{$tag} class=\"$classes\">";

/**
 * @since 1.0
 *
 * @param Display_Posts $this
 * @param Partials      $partial
 */
do_action( 'Easy_Plugins/Display_Posts/Post/Before', $this, $partial );

echo $image;
echo ' ' . $title;
echo ' ' . $date;
echo ' ' . $author;
echo ' ' . $terms;
echo ' ' . $excerpt;
echo $content;

/**
 * @since 1.0
 *
 * @param Display_Posts $this
 * @param Partials      $partial
 */
do_action( 'Easy_Plugins/Display_Posts/Post/After', $this, $partial );

echo "</{$tag}>";
