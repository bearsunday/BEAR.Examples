<?php
/**
 * @var \MyVendor\Cms\Entity\Author $author
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{h $author->name }} - BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level{{h $cssLevel }}.css">
</head>
<body>
  <header>
    <h1 class="Author">Author Detail</h1>
  </header>
  <main>
    <section class="Author">
      <input type="hidden" class="id" value="{{h $author->id }}">
      <h2 class="name">{{h $author->name }}</h2>
      <a class="email" href="mailto:{{h $author->email }}">{{h $author->email }}</a>
      <p class="bio">{{h $author->bio }}</p>
    </section>
  </main>
  <nav>
    <h2>Links</h2>
    <ul>
      <li><a href="/articlelist" class="goArticleList" title="Browse all articles.">Browse all articles</a></li>
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
