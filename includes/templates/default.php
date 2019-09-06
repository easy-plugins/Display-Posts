<?php

/**
 * @var string $inner_wrapper
 * @var array  $class
 * @var string $image
 * @var string $title
 * @var string $date
 * @var string $author
 * @var string $terms
 * @var string $excerpt
 * @var string $content
 */
echo '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '">' . $image . ' ' . $title . ' ' . $date . ' ' . $author . ' ' . $terms . ' ' . $excerpt . $content . '</' . $inner_wrapper . '>';
