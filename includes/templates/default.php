<?php

/**
 * @var string $item_tag
 * @var array  $class
 * @var string $image
 * @var string $title
 * @var string $date
 * @var string $author
 * @var string $terms
 * @var string $excerpt
 * @var string $content
 */
echo '<' . $item_tag . ' class="' . implode( ' ', $class ) . '">' . $image . ' ' . $title . ' ' . $date . ' ' . $author . ' ' . $terms . ' ' . $excerpt . $content . '</' . $item_tag . '>';
