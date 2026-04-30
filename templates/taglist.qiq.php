<?php
/**
 * @var list<\MyVendor\Cms\Entity\Tag> $tags
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tag List - BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level{{h $cssLevel }}.css">
</head>
<body>
  <header>
    <h1 class="TagList">Tags</h1>
  </header>
  <main>
    <section class="TagList">
      <?php if ($tags === []): ?>
        <p>No tags defined yet.</p>
      <?php endif ?>
      <ul>
        <?php foreach ($tags as $tag): ?>
          <li class="Tag">
            <input type="hidden" class="id" value="{{h $tag->id }}">
            <a class="goTag" href="/tag?id={{h $tag->id }}">
              <span class="name">{{h $tag->name }}</span>
              <span class="slug">{{h $tag->slug }}</span>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    </section>
  </main>
  <nav>
    <h2>Links</h2>
    <ul>
      <li><a href="/articlelist" class="goArticleList">Browse all articles</a></li>
      <li><a href="/" class="goIndex">Home</a></li>
    </ul>
  </nav>
  <footer>
    <p>BEAR.Cms Reference CMS</p>
    <p class="cssSwitcher">CSS:
      <?php foreach ([1, 2, 3] as $n): ?>
        <?php if ($n === $cssLevel): ?>
          <strong>level{{h $n }}</strong>
        <?php else: ?>
          <a href="{{h $cssLinks[$n] }}">level{{h $n }}</a>
        <?php endif ?>
      <?php endforeach ?>
    </p>
  </footer>
</body>
</html>
