<?php
/**
 * @var int $cssLevel
 * @var array<int, string> $cssLinks
 */
?>
<p class="cssSwitcher">CSS:
  <?php foreach ([1, 2, 3] as $n): ?>
    <?php if ($n === $cssLevel): ?>
      <strong>level{{h $n }}</strong>
    <?php else: ?>
      <a href="{{h $cssLinks[$n] }}">level{{h $n }}</a>
    <?php endif ?>
  <?php endforeach ?>
</p>
