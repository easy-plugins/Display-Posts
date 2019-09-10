<?php

/**
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
echo '<' . $tag . ' class="' . implode( ' ', $class ) . '">' . $image . ' ' . $title . ' ' . $date . ' ' . $author . ' ' . $terms . ' ' . $excerpt . $content . '</' . $tag . '>';
